<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\SystemNotification;
use App\Services\TopicSimilarityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Feature 3: Supervisor Quota & Topic Proposal Bidding System (Prattay)
 * Feature 4: Duplicate Topic & Title Similarity Checker (Prattay)
 */
class ProposalBiddingController extends Controller
{
    public function __construct(private TopicSimilarityService $similarityService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $statusFilter = trim((string) $request->query('status', ''));
        $teamFilter = trim((string) $request->query('team_id', ''));
        $searchQuery = trim((string) $request->query('q', ''));

        $proposals = Proposal::with(['team', 'supervisor'])
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->when($teamFilter !== '', fn ($q) => $q->where('team_id', (int) $teamFilter))
            ->when($searchQuery !== '', function ($q) use ($searchQuery) {
                $q->where(function ($sub) use ($searchQuery) {
                    $sub->where('title', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('tech_stack', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('problem_statement', 'LIKE', "%{$searchQuery}%");
                });
            })
            ->latest('id')
            ->get();

        $supervisors = User::whereIn('role', ['supervisor', 'coordinator'])->get();
        $allTeams = Team::all();

        $teams = $user->role === 'student'
            ? Team::whereHas('members', fn($q) => $q->where('student_id', $user->id))->orWhere('leader_id', $user->id)->get()
            : $allTeams;

        return view('proposals.index', compact('proposals', 'supervisors', 'teams', 'allTeams', 'statusFilter', 'teamFilter', 'searchQuery'));
    }

    /**
     * Feature 4 (Defend): Standalone "Check Similarity Before Submit" endpoint.
     * Returns top 3 corpus matches, overlapping words, and risk band without creating a bid.
     */
    public function checkSimilarity(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'problem_statement' => 'required|string',
            'team_id' => 'nullable|integer|exists:teams,id',
        ]);

        $scan = $this->similarityService->analyzeProposalSimilarity(
            $validated['title'],
            $validated['problem_statement'],
            isset($validated['team_id']) ? (int) $validated['team_id'] : null
        );

        return back()
            ->with('similarity_preview', [
                'title' => $validated['title'],
                'problem_statement' => $validated['problem_statement'],
                ...$scan,
            ])
            ->withInput()
            ->with('success', "Feature 4 Pre-Check Complete: {$scan['similarity_score']}% similarity ({$scan['matched_archive_title']}).");
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null && $user->role === 'student', 403);

        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'supervisor_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'problem_statement' => 'required|string',
            'features_multiline' => 'required|string',
            'tech_stack' => 'required|string|max:150',
        ]);

        $teamId = (int) $validated['team_id'];

        // Verify authenticated student belongs to the submitting team
        $belongsToTeam = TeamMember::where('team_id', $teamId)->where('student_id', $user->id)->exists()
            || Team::where('id', $teamId)->where('leader_id', $user->id)->exists();

        if (!$belongsToTeam) {
            return back()->with('error', 'Access denied: You can only submit proposals for your own team.')->withInput();
        }

        // 1. Block prohibited non-MVC / Python / MERN frameworks
        if (preg_match('/\b(mern|mean|django|flask|fastapi|python|mongodb|express|nextjs|next\.js|nestjs)\b/i', $validated['tech_stack'])) {
            return back()->with('error', 'CSE 470 Tech Constraint Violation: MERN, Django, Flask, and Python web frameworks are strictly prohibited!')->withInput();
        }

        // 2. Positively verify Laravel / PHP / Blade / MySQL MVC stack compliance
        $stackLower = strtolower($validated['tech_stack']);
        $hasLaravelOrPhp = str_contains($stackLower, 'laravel') || str_contains($stackLower, 'php');
        $hasMvcOrBlade = str_contains($stackLower, 'mvc') || str_contains($stackLower, 'blade');
        $hasMysql = str_contains($stackLower, 'mysql') || str_contains($stackLower, 'mariadb') || str_contains($stackLower, 'sql');

        if (!$hasLaravelOrPhp || !$hasMvcOrBlade || !$hasMysql) {
            return back()->with('error', 'CSE 470 MVC Stack Requirement: Tech stack must explicitly specify Laravel/PHP, MVC/Blade, and MySQL.')->withInput();
        }

        // 3. Prevent duplicate active bids from the same team
        $hasActiveProposal = Proposal::where('team_id', $teamId)
            ->whereIn('status', ['pending', 'revision_required', 'approved'])
            ->exists();

        if ($hasActiveProposal) {
            return back()->with('error', 'Your team already has a pending, revision-required, or approved proposal bid.')->withInput();
        }

        $featuresList = array_values(array_filter(array_map('trim', explode("\n", $validated['features_multiline']))));

        // 4. Validate exactly 20 non-empty substantive features per CSE 470 Rule Book
        if (count($featuresList) !== 20) {
            return back()->with('error', 'CSE 470 Rule Book Requirement: Project proposals must contain exactly 20 substantive features (received ' . count($featuresList) . ').')->withInput();
        }

        // Feature 4: Run Automated Similarity Check against Archives & Active Proposals
        $similarityResult = $this->similarityService->analyzeProposalSimilarity(
            $validated['title'],
            $validated['problem_statement'],
            $teamId
        );

        $errorMessage = null;
        $proposal = null;

        DB::transaction(function () use ($validated, $teamId, $featuresList, $similarityResult, &$errorMessage, &$proposal) {
            $supervisor = User::where('id', $validated['supervisor_id'])->lockForUpdate()->firstOrFail();

            if (!in_array($supervisor->role, ['supervisor', 'coordinator'], true)) {
                $errorMessage = 'Selected user is not a valid faculty supervisor.';
                return;
            }

            // Lock existing proposals and teams for this supervisor to prevent concurrent quota overflow
            Proposal::where('supervisor_id', $supervisor->id)->lockForUpdate()->get();
            Team::where('supervisor_id', $supervisor->id)->lockForUpdate()->get();

            if ($supervisor->remainingSupervisorSlots() <= 0) {
                $errorMessage = 'Selected Supervisor has reached maximum group quota (including pending bids).';
                return;
            }

            $proposal = Proposal::create([
                'team_id' => $teamId,
                'supervisor_id' => $supervisor->id,
                'title' => $validated['title'],
                'problem_statement' => $validated['problem_statement'],
                'proposed_features' => $featuresList,
                'tech_stack' => $validated['tech_stack'],
                'similarity_score' => $similarityResult['similarity_score'],
                'matched_archive_title' => $similarityResult['matched_archive_title'],
                'status' => 'pending',
            ]);

            SystemNotification::create([
                'user_id' => $supervisor->id,
                'title' => 'New Project Proposal Bid Submitted',
                'message' => "Proposal '{$proposal->title}' submitted with {$similarityResult['similarity_score']}% similarity score.",
                'type' => 'approval',
            ]);
        });

        if ($errorMessage !== null) {
            return back()->with('error', $errorMessage)->withInput();
        }

        return back()->with('success', "Features 3 & 4: Proposal submitted! Similarity Score: {$similarityResult['similarity_score']}% (Closest Match: {$similarityResult['matched_archive_title']}).");
    }

    public function review(Request $request, Proposal $proposal)
    {
        $user = $request->user();
        abort_unless($user !== null && in_array($user->role, ['supervisor', 'coordinator'], true), 403);

        if ($user->role === 'supervisor' && (int) $proposal->supervisor_id !== (int) $user->id) {
            return back()->with('error', 'Access denied: Only the target supervisor or coordinator can review this proposal.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,revision_required,rejected',
            'supervisor_feedback' => 'required|string|max:1000',
        ]);

        $quotaError = null;

        DB::transaction(function () use ($proposal, $validated, &$quotaError) {
            $supervisor = User::where('id', $proposal->supervisor_id)->lockForUpdate()->firstOrFail();

            if ($validated['status'] === 'approved') {
                $supervisedOtherTeams = Team::where('supervisor_id', $supervisor->id)
                    ->where('id', '!=', $proposal->team_id)
                    ->pluck('id')
                    ->toArray();
                $otherActiveProposalTeams = Proposal::where('supervisor_id', $supervisor->id)
                    ->where('id', '!=', $proposal->id)
                    ->whereIn('status', ['pending', 'revision_required', 'approved'])
                    ->pluck('team_id')
                    ->toArray();
                $otherCommittedCount = count(array_unique(array_merge($supervisedOtherTeams, $otherActiveProposalTeams)));

                if ($otherCommittedCount >= (int) $supervisor->max_group_quota) {
                    $quotaError = 'Cannot approve proposal: Supervisor has already reached maximum supervision quota.';
                    return;
                }
            }

            $proposal->update($validated);

            if ($validated['status'] === 'approved') {
                $proposal->team()->update([
                    'supervisor_id' => $proposal->supervisor_id,
                    'status' => 'supervised',
                ]);
            }
        });

        if ($quotaError !== null) {
            return back()->with('error', $quotaError);
        }

        return back()->with('success', 'Feature 3: Supervisor decision and feedback saved.');
    }
}
