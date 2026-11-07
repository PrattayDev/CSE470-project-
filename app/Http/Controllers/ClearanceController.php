<?php

namespace App\Http\Controllers;

use App\Models\ClearanceCertificate;
use App\Models\Proposal;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\PenaltyCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Feature 19: Automated Certificate & Clearance Sheet Generator
 */
class ClearanceController extends Controller
{
    public function __construct(private PenaltyCalculationService $penaltyService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user() ?? Auth::user();
        abort_unless($user !== null, 401);

        if ($user->role === 'student') {
            $teams = Team::whereHas('members', fn ($q) => $q->where('student_id', $user->id))
                ->orWhere('leader_id', $user->id)
                ->get();
            $teamIds = $teams->pluck('id')->toArray();
            $certificates = ClearanceCertificate::with(['team.members.student', 'issuer'])
                ->whereIn('team_id', $teamIds)
                ->get();
        } elseif ($user->role === 'supervisor') {
            $supervisedTeamIds = Team::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $proposalTeamIds = Proposal::where('supervisor_id', $user->id)
                ->whereIn('status', ['pending', 'revision_required', 'approved'])
                ->pluck('team_id')
                ->toArray();
            $allowedTeamIds = array_values(array_unique(array_merge($supervisedTeamIds, $proposalTeamIds)));

            $teams = Team::whereIn('id', $allowedTeamIds)->get();
            $certificates = ClearanceCertificate::with(['team.members.student', 'issuer'])
                ->whereIn('team_id', $allowedTeamIds)
                ->get();
        } else {
            // Lab Admin or Coordinator
            $teams = Team::all();
            $certificates = ClearanceCertificate::with(['team.members.student', 'issuer'])->get();
        }

        $eligibilityMap = [];
        foreach ($teams as $team) {
            $eligibilityMap[$team->id] = $this->penaltyService->verifyTeamClearanceEligibility($team->id);
        }

        return view('clearance.index', compact('certificates', 'teams', 'eligibilityMap'));
    }

    public function generate(Request $request)
    {
        $user = $request->user() ?? Auth::user();
        if (!$user || !in_array($user->role, ['lab_admin', 'supervisor', 'coordinator'], true)) {
            abort(403, 'Only Lab Administrators, Supervisors, or Coordinators can issue official clearance certificates.');
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        if ($user->role === 'supervisor' && !$this->canAccessTeamClearance($user, (int) $validated['team_id'])) {
            abort(403, 'Access denied: Supervisors can only generate clearance certificates for their own supervised teams.');
        }

        $check = $this->penaltyService->verifyTeamClearanceEligibility((int) $validated['team_id']);
        if (!$check['is_eligible']) {
            return back()->with('error', 'Clearance Blocked: Team must have 0 unreturned lab items, 0 BDT unpaid fines, and an approved SRS document!');
        }

        $serial = 'BRACU-CSE470-' . date('Y') . '-GRP' . $validated['team_id'] . '-' . strtoupper(substr(md5(uniqid()), 0, 5));

        $cert = ClearanceCertificate::updateOrCreate(
            ['team_id' => $validated['team_id']],
            [
                'certificate_serial' => $serial,
                'lab_dues_cleared' => $check['unreturned_equipment_count'] === 0 && $check['outstanding_fine_bdt'] <= 0.0,
                'final_code_submitted' => true,
                'srs_approved' => $check['srs_approved'],
                'issued_by' => $user->id,
                'issued_at' => now(),
            ]
        );

        Team::where('id', $validated['team_id'])->update(['status' => 'cleared']);

        return back()->with('success', "Feature 19: Official Clearance Certificate generated (Serial: {$cert->certificate_serial}).");
    }

    public function printView(Request $request, ClearanceCertificate $certificate)
    {
        $user = $request->user() ?? Auth::user();
        abort_unless($user !== null, 401);

        if (!$this->canAccessTeamClearance($user, (int) $certificate->team_id)) {
            abort(403, 'Access denied: You are only authorized to view your own team or supervised team clearance certificate.');
        }

        $certificate->load(['team.members.student', 'team.supervisor', 'issuer']);
        return view('clearance.print', compact('certificate'));
    }

    private function canAccessTeamClearance(User $user, int $teamId): bool
    {
        if (in_array($user->role, ['coordinator', 'lab_admin'], true)) {
            return true;
        }

        if ($user->role === 'student') {
            return TeamMember::where('team_id', $teamId)->where('student_id', $user->id)->exists()
                || Team::where('id', $teamId)->where('leader_id', $user->id)->exists();
        }

        if ($user->role === 'supervisor') {
            if (Team::where('id', $teamId)->where('supervisor_id', $user->id)->exists()) {
                return true;
            }
            return Proposal::where('team_id', $teamId)
                ->where('supervisor_id', $user->id)
                ->whereIn('status', ['pending', 'revision_required', 'approved'])
                ->exists();
        }

        return false;
    }
}
