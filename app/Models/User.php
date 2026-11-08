<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'student_or_faculty_id', 'name', 'email', 'password', 'role',
        'department', 'section', 'cgpa', 'skills', 'research_interests',
        'max_group_quota', 'github_username', 'phone'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'skills' => 'array',
        'cgpa' => 'decimal:2',
    ];

    public function ledTeams()
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    public function supervisedTeams()
    {
        return $this->hasMany(Team::class, 'supervisor_id');
    }

    public function supervisorProposals()
    {
        return $this->hasMany(Proposal::class, 'supervisor_id');
    }

    public function activeSupervisorProposals()
    {
        return $this->hasMany(Proposal::class, 'supervisor_id')
            ->whereIn('status', ['pending', 'revision_required', 'approved']);
    }

    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class, 'student_id');
    }

    public function activeTeam()
    {
        $membership = $this->teamMemberships()->with('team')->first();
        return $membership ? $membership->team : null;
    }

    /**
     * Calculate remaining supervision quota slots by counting both assigned supervised teams
     * and pending/revision_required/approved proposal bids targeting this supervisor.
     */
    public function remainingSupervisorSlots(): int
    {
        if (!in_array($this->role, ['supervisor', 'coordinator'], true)) {
            return 0;
        }

        $supervisedTeamIds = $this->supervisedTeams()->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        $activeProposals = $this->activeSupervisorProposals()->get(['id', 'team_id', 'status']);
        $proposalTeamIds = $activeProposals->pluck('team_id')->map(fn ($id) => (int) $id)->toArray();

        $distinctTeamsCommitted = count(array_unique(array_merge($supervisedTeamIds, $proposalTeamIds)));

        // Also count any additional pending/revision_required bids beyond 1 per team so every pending bid reserves capacity
        $extraPendingBids = max(0, $activeProposals->count() - count(array_unique($proposalTeamIds)));

        $totalReservedSlots = $distinctTeamsCommitted + $extraPendingBids;

        return max(0, (int) $this->max_group_quota - $totalReservedSlots);
    }

    /**
     * Feature 1: Calculate profile completeness percentage across key academic fields.
     */
    public function profileCompleteness(): int
    {
        $checks = [
            !empty($this->name),
            !empty($this->section),
            is_array($this->skills) && count($this->skills) >= 2,
            !empty($this->research_interests),
            !empty($this->github_username),
            $this->role === 'student' ? ($this->cgpa !== null) : ((int) $this->max_group_quota > 0),
        ];

        $filled = count(array_filter($checks));
        return (int) round(($filled / count($checks)) * 100);
    }

    /**
     * Feature 1 (Defend): Compute Supervisor Match Score (%) between this user and a supervisor
     * based on normalized technical skill tags and research interest domain tokens.
     */
    public function calculateSupervisorMatch(User $supervisor): array
    {
        $myTokens = $this->extractProfileTokens();
        $supTokens = $supervisor->extractProfileTokens();

        if (empty($myTokens) || empty($supTokens)) {
            return ['score' => 0, 'matched_terms' => [], 'shared_tokens' => []];
        }

        $matched = array_values(array_intersect($myTokens, $supTokens));
        $denominator = max(1, min(count($myTokens), count($supTokens)));
        $score = (int) min(100, round((count($matched) / $denominator) * 100));

        return [
            'score' => $score,
            'matched_terms' => $matched,
            'shared_tokens' => $matched,
        ];
    }

    public function extractProfileTokens(): array
    {
        $skillTokens = [];
        foreach ($this->skills ?? [] as $skill) {
            $clean = strtolower(trim((string) $skill));
            if ($clean !== '') {
                $skillTokens[] = $clean;
                foreach (preg_split('/[^a-z0-9+#]+/i', $clean) ?: [] as $part) {
                    if (strlen($part) >= 2) {
                        $skillTokens[] = strtolower($part);
                    }
                }
            }
        }

        $interestText = strtolower((string) $this->research_interests);
        $stopWords = ['and', 'the', 'for', 'with', 'in', 'of', 'to', 'on', 'by'];
        $interestWords = array_filter(
            preg_split('/[^a-z0-9+#]+/i', $interestText) ?: [],
            fn ($w) => strlen($w) >= 3 && !in_array($w, $stopWords, true)
        );

        return array_values(array_unique(array_merge($skillTokens, $interestWords)));
    }
}
