<?php

namespace App\Http\Controllers;

use App\Models\ComplianceDisclosure;
use App\Models\Proposal;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\TopicSimilarityService;
use Illuminate\Http\Request;

/**
 * Feature 17: Plagiarism & AI-Disclosure Compliance Tracker (Rownak)
 */
class ComplianceController extends Controller
{
    public function __construct(private TopicSimilarityService $similarityService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if ($user->role === 'student') {
            $teams = Team::whereHas('members', fn ($q) => $q->where('student_id', $user->id))
                ->orWhere('leader_id', $user->id)
                ->get();
            $teamIds = $teams->pluck('id')->toArray();

            $disclosures = ComplianceDisclosure::with('team')
                ->whereIn('team_id', $teamIds)
                ->latest('id')
                ->get();
        } elseif ($user->role === 'supervisor') {
            $supervisedTeamIds = Team::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $proposalTeamIds = Proposal::where('supervisor_id', $user->id)
                ->whereIn('status', ['pending', 'revision_required', 'approved'])
                ->pluck('team_id')
                ->toArray();
            $allowedTeamIds = array_values(array_unique(array_merge($supervisedTeamIds, $proposalTeamIds)));

            $teams = Team::whereIn('id', $allowedTeamIds)->get();
            $disclosures = ComplianceDisclosure::with('team')
                ->whereIn('team_id', $allowedTeamIds)
                ->latest('id')
                ->get();
        } else {
            $teams = Team::all();
            $disclosures = ComplianceDisclosure::with('team')->latest('id')->get();
        }

        // Feature 17 (Defend): Distinct Composer Dependency & AI Explanation Audit + Plagiarism Check
        $installedPackages = $this->getInstalledComposerPackages();
        $plagiarismChecks = [];
        foreach ($disclosures as $d) {
            $teamProposal = Proposal::where('team_id', $d->team_id)->latest('id')->first();
            $scanResult = $this->similarityService->analyzeProposalSimilarity(
                $teamProposal->title ?? $d->influenced_modules,
                ($teamProposal->problem_statement ?? '') . ' ' . $d->ai_usage_purpose,
                (int) $d->team_id
            );

            $audit = $this->auditDisclosureAgainstComposer($d, $installedPackages);
            $plagiarismChecks[$d->id] = array_merge($scanResult, $audit);
        }

        return view('compliance.index', compact('disclosures', 'teams', 'plagiarismChecks', 'installedPackages'));
    }

    private function getInstalledComposerPackages(): array
    {
        $composerPath = base_path('composer.json');
        if (!is_file($composerPath)) {
            return ['laravel/framework', 'laravel/sanctum', 'guzzlehttp/guzzle', 'phpunit/phpunit'];
        }
        $json = json_decode((string) file_get_contents($composerPath), true) ?: [];
        $req = array_keys($json['require'] ?? []);
        $reqDev = array_keys($json['require-dev'] ?? []);
        return array_values(array_filter(array_merge($req, $reqDev), fn ($pkg) => $pkg !== 'php'));
    }

    private function auditDisclosureAgainstComposer(ComplianceDisclosure $d, array $installedPackages): array
    {
        $declaredRaw = strtolower((string) $d->third_party_packages);
        $matchedComposerPackages = [];
        foreach ($installedPackages as $pkg) {
            $shortName = explode('/', strtolower($pkg))[1] ?? strtolower($pkg);
            if (str_contains($declaredRaw, strtolower($pkg)) || str_contains($declaredRaw, $shortName)) {
                $matchedComposerPackages[] = $pkg;
            }
        }

        $purposeWordCount = str_word_count((string) $d->ai_usage_purpose);
        $moduleItems = array_filter(preg_split('/[,;\n]+/', (string) $d->influenced_modules) ?: []);
        $hasAdequateExplanation = $purposeWordCount >= max(5, count($moduleItems) * 3);

        $auditScore = 60;
        if (!empty($matchedComposerPackages)) {
            $auditScore += 20;
        }
        if ($hasAdequateExplanation) {
            $auditScore += 20;
        }

        return [
            'matched_composer_packages' => $matchedComposerPackages,
            'has_adequate_explanation' => $hasAdequateExplanation,
            'compliance_audit_score' => $auditScore,
        ];
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null && $user->role === 'student', 403);

        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'ai_tools_used' => 'required|string|max:255',
            'ai_usage_purpose' => 'required|string|max:1000',
            'influenced_modules' => 'required|string|max:1000',
            'third_party_packages' => 'required|string|max:1000',
            'student_ownership_verified' => 'accepted',
        ], [
            'student_ownership_verified.accepted' => 'You must explicitly confirm Section 8.1 student code ownership and plagiarism compliance.',
        ]);

        $teamId = (int) $validated['team_id'];
        $belongsToTeam = TeamMember::where('team_id', $teamId)->where('student_id', $user->id)->exists()
            || Team::where('id', $teamId)->where('leader_id', $user->id)->exists();

        if (!$belongsToTeam) {
            return back()->withErrors(['team_id' => 'Unauthorized: You can only submit compliance disclosures for your own team.'])->withInput();
        }

        ComplianceDisclosure::create([
            'team_id' => $teamId,
            'ai_tools_used' => $validated['ai_tools_used'],
            'ai_usage_purpose' => $validated['ai_usage_purpose'],
            'influenced_modules' => $validated['influenced_modules'],
            'third_party_packages' => $validated['third_party_packages'],
            'student_ownership_verified' => true,
            'supervisor_verified' => false,
        ]);

        return back()->with('success', 'Feature 17: Section 8.1 Plagiarism & AI-Use Disclosure logged.');
    }

    public function verify(Request $request, ComplianceDisclosure $disclosure)
    {
        $user = $request->user();
        abort_unless($user !== null && in_array($user->role, ['supervisor', 'coordinator'], true), 403);

        if ($user->role === 'supervisor' && $disclosure->team && $disclosure->team->supervisor_id && (int) $disclosure->team->supervisor_id !== (int) $user->id) {
            return back()->with('error', 'Access denied: Only the team supervisor or coordinator can verify this disclosure.');
        }

        $disclosure->update(['supervisor_verified' => true]);
        return back()->with('success', 'Feature 17: Compliance disclosure verified by Supervisor.');
    }
}
