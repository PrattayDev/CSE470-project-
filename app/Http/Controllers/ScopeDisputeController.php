<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ScopeChangeRequest;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\Request;

/**
 * Feature 18: Group Dispute & Scope Change Request Module (Rownak)
 */
class ScopeDisputeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if ($user->role === 'student') {
            $teams = Team::whereHas('members', fn ($q) => $q->where('student_id', $user->id))
                ->orWhere('leader_id', $user->id)
                ->get();
            $teamIds = $teams->pluck('id')->toArray();

            $requests = ScopeChangeRequest::with(['team', 'requester'])
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
            $requests = ScopeChangeRequest::with(['team', 'requester'])
                ->whereIn('team_id', $allowedTeamIds)
                ->latest('id')
                ->get();
        } else {
            $teams = Team::all();
            $requests = ScopeChangeRequest::with(['team', 'requester'])->latest('id')->get();
        }

        return view('disputes.index', compact('requests', 'teams'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null && $user->role === 'student', 403);

        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'request_type' => 'required|in:feature_change,member_dispute,stack_adjustment',
            'current_scope_detail' => 'required|string|max:1000',
            'proposed_change_detail' => 'required|string|max:1000',
            'reason_justification' => 'required|string|max:1000',
        ]);

        $teamId = (int) $validated['team_id'];
        $belongsToTeam = TeamMember::where('team_id', $teamId)->where('student_id', $user->id)->exists()
            || Team::where('id', $teamId)->where('leader_id', $user->id)->exists();

        if (!$belongsToTeam) {
            return back()->withErrors(['team_id' => 'Unauthorized: You can only file scope or dispute requests for your own team.'])->withInput();
        }

        ScopeChangeRequest::create([
            'team_id' => $teamId,
            'requested_by' => $user->id,
            'request_type' => $validated['request_type'],
            'current_scope_detail' => $validated['current_scope_detail'],
            'proposed_change_detail' => $validated['proposed_change_detail'],
            'reason_justification' => $validated['reason_justification'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Feature 18: Formal Scope Change / Dispute ticket logged for Coordinator review.');
    }

    public function decide(Request $request, ScopeChangeRequest $scopeChangeRequest)
    {
        $user = $request->user();
        abort_unless($user !== null && $user->role === 'coordinator', 403);

        $validated = $request->validate([
            'status' => 'required|in:under_review,approved,rejected',
            'coordinator_decision_note' => 'required|string|max:1000',
        ]);

        $scopeChangeRequest->update($validated);

        // Feature 18 (Defend): When approved, automatically apply the scope/stack change or notify team members
        if ($validated['status'] === 'approved') {
            $proposal = Proposal::where('team_id', $scopeChangeRequest->team_id)->latest('id')->first();

            if ($scopeChangeRequest->request_type === 'feature_change' && $proposal) {
                $features = is_array($proposal->proposed_features) ? $proposal->proposed_features : [];
                $replaced = false;
                foreach ($features as $idx => $feat) {
                    if (stripos((string) $feat, trim($scopeChangeRequest->current_scope_detail)) !== false) {
                        $features[$idx] = $scopeChangeRequest->proposed_change_detail;
                        $replaced = true;
                        break;
                    }
                }
                if (!$replaced && !empty($features)) {
                    $features[count($features) - 1] = $scopeChangeRequest->proposed_change_detail;
                } elseif (empty($features)) {
                    $features[] = $scopeChangeRequest->proposed_change_detail;
                }
                $proposal->update(['proposed_features' => $features]);
            } elseif ($scopeChangeRequest->request_type === 'stack_adjustment') {
                Team::where('id', $scopeChangeRequest->team_id)->update([
                    'preferred_stack' => $scopeChangeRequest->proposed_change_detail,
                ]);
                if ($proposal) {
                    $proposal->update(['tech_stack' => $scopeChangeRequest->proposed_change_detail]);
                }
            }
        }

        return back()->with('success', 'Feature 18: Coordinator resolution recorded and system state updated.');
    }
}
