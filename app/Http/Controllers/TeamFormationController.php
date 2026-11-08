<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Feature 2: Smart Team Formation & Teammate Finder (Prattay)
 */
class TeamFormationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $teams = Team::with(['leader', 'supervisor', 'members.student'])->get();

        $searchSkill = trim((string) $request->query('skill', ''));
        $searchSection = trim((string) $request->query('section', ''));

        $assignedStudentIds = TeamMember::pluck('student_id')->toArray();

        $availableStudents = User::where('role', 'student')
            ->when($searchSection !== '', fn ($q) => $q->where('section', $searchSection))
            ->get()
            ->filter(function (User $student) use ($searchSkill) {
                if ($searchSkill === '') {
                    return true;
                }
                $target = strtolower($searchSkill);
                foreach ($student->skills ?? [] as $tag) {
                    $normalizedTag = strtolower(trim((string) $tag));
                    if ($normalizedTag === $target) {
                        return true;
                    }
                    $words = preg_split('/[\s\/\-]+/', $normalizedTag) ?: [];
                    if (in_array($target, $words, true)) {
                        return true;
                    }
                }
                return false;
            })
            ->map(function ($student) use ($assignedStudentIds) {
                $student->has_group = in_array($student->id, $assignedStudentIds);
                return $student;
            })
            ->values();

        $invitations = TeamInvitation::with(['team', 'invitedStudent'])
            ->when($user->role === 'student', function ($q) use ($user) {
                $myTeamIds = TeamMember::where('student_id', $user->id)->pluck('team_id')->toArray();
                $q->where('invited_student_id', $user->id)
                  ->orWhereIn('team_id', $myTeamIds);
            })
            ->latest('id')
            ->get();

        $myTeams = $user->role === 'student'
            ? Team::whereHas('members', fn($q) => $q->where('student_id', $user->id))->orWhere('leader_id', $user->id)->get()
            : collect();

        return view('teams.index', compact('teams', 'availableStudents', 'invitations', 'searchSkill', 'searchSection', 'myTeams'));
    }

    public function storeTeam(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null && $user->role === 'student', 403);

        $validated = $request->validate([
            'group_name' => 'required|string|max:100|unique:teams,group_name',
            'section' => 'required|string|max:20',
            'preferred_stack' => 'required|string|max:120',
            'domain_interest' => 'required|string|max:150',
        ]);

        if (TeamMember::where('student_id', $user->id)->exists()) {
            return back()->with('error', 'Feature 2 Rule: You are already a registered member of a CSE 470 project group.');
        }

        DB::transaction(function () use ($validated, $user) {
            $team = Team::create([
                'group_name' => $validated['group_name'],
                'section' => $validated['section'],
                'leader_id' => $user->id,
                'status' => 'forming',
                'preferred_stack' => $validated['preferred_stack'],
                'domain_interest' => $validated['domain_interest'],
            ]);

            TeamMember::create([
                'team_id' => $team->id,
                'student_id' => $user->id,
                'assigned_module' => 'Team Leader & Core Module',
            ]);
        });

        return back()->with('success', 'Feature 2: New 4-Member Group initialized successfully.');
    }

    public function sendInvite(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null && $user->role === 'student', 403);

        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'invited_student_id' => 'required|integer|exists:users,id',
            'message' => 'nullable|string|max:255',
        ]);

        $team = Team::findOrFail($validated['team_id']);

        // Verify sender belongs to the team
        $isSenderInTeam = (int) $team->leader_id === (int) $user->id
            || TeamMember::where('team_id', $team->id)->where('student_id', $user->id)->exists();

        if (!$isSenderInTeam) {
            return back()->with('error', 'Access denied: You can only send invitations on behalf of your own team.');
        }

        // Verify target user is strictly a student
        $invitedStudent = User::where('id', $validated['invited_student_id'])
            ->where('role', 'student')
            ->first();

        if (!$invitedStudent) {
            return back()->with('error', 'Invalid invitation target: Only student accounts can be invited to project groups.');
        }

        if (TeamMember::where('student_id', $invitedStudent->id)->exists()) {
            return back()->with('error', 'Selected student is already assigned to a CSE 470 project group.');
        }

        $memberCount = TeamMember::where('team_id', $team->id)->count();
        if ($memberCount >= 4 || $team->status !== 'forming') {
            return back()->with('error', 'CSE 470 Rule Violation: Group is already full (4/4 members) or locked.');
        }

        $alreadyInvited = TeamInvitation::where('team_id', $team->id)
            ->where('invited_student_id', $invitedStudent->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyInvited) {
            return back()->with('error', 'A pending invitation has already been sent to this student.');
        }

        TeamInvitation::create([
            'team_id' => $team->id,
            'invited_student_id' => $invitedStudent->id,
            'message' => $validated['message'] ?? 'Join our CSE 470 MVC Team!',
            'status' => 'pending',
        ]);

        return back()->with('success', 'Feature 2: Teammate invitation sent.');
    }

    public function respondInvite(Request $request, TeamInvitation $invitation)
    {
        $user = $request->user();
        abort_unless($user !== null && $user->role === 'student', 403);

        if ((int) $invitation->invited_student_id !== (int) $user->id) {
            return back()->with('error', 'Access denied: Only the invited student can accept or decline this invitation.');
        }

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'This invitation has already been processed.');
        }

        $validated = $request->validate([
            'decision' => 'required|in:accepted,declined',
            'assigned_module' => 'nullable|string|max:150',
        ]);

        if ($validated['decision'] === 'declined') {
            $invitation->update(['status' => 'declined']);
            return back()->with('success', 'Feature 2: Team invitation declined.');
        }

        $errorMessage = null;

        DB::transaction(function () use ($invitation, $validated, &$errorMessage) {
            $team = Team::where('id', $invitation->team_id)->lockForUpdate()->firstOrFail();
            $currentCount = TeamMember::where('team_id', $team->id)->lockForUpdate()->count();

            if ($currentCount >= 4) {
                $errorMessage = 'CSE 470 Rule Violation: Maximum 4 students allowed per group.';
                return;
            }

            if (TeamMember::where('student_id', $invitation->invited_student_id)->lockForUpdate()->exists()) {
                $errorMessage = 'You have already joined another CSE 470 group.';
                return;
            }

            TeamMember::create([
                'team_id' => $team->id,
                'student_id' => $invitation->invited_student_id,
                'assigned_module' => $validated['assigned_module'] ?? 'Assigned Core MVC Module',
            ]);

            $invitation->update(['status' => 'accepted']);

            if ($currentCount + 1 >= 4) {
                $team->update(['status' => 'locked']);
                TeamInvitation::where('team_id', $team->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'declined']);
            }
        });

        if ($errorMessage !== null) {
            return back()->with('error', $errorMessage);
        }

        return back()->with('success', 'Feature 2: Team invitation accepted and membership recorded atomically.');
    }

    public function removeMember(Request $request, Team $team, TeamMember $member)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if ((int) $member->team_id !== (int) $team->id) {
            abort(404);
        }

        $isLeader = (int) $team->leader_id === (int) $user->id;
        $isSelf = (int) $member->student_id === (int) $user->id;
        $isCoordinator = $user->role === 'coordinator';

        if (!$isLeader && !$isSelf && !$isCoordinator) {
            return back()->with('error', 'Access denied: Only the team leader, the member themselves, or the coordinator can remove a team member.');
        }

        if ((int) $member->student_id === (int) $team->leader_id && TeamMember::where('team_id', $team->id)->count() > 1) {
            return back()->with('error', 'Feature 2 Rule: The team leader cannot leave while other members remain in the group.');
        }

        if (in_array($team->status, ['supervised', 'cleared'], true) && !$isCoordinator) {
            return back()->with('error', 'Feature 2 Rule: Supervised or cleared teams require Coordinator approval (F18) to remove members.');
        }

        DB::transaction(function () use ($team, $member) {
            $member->delete();
            $remaining = TeamMember::where('team_id', $team->id)->count();
            if ($remaining < 4 && $team->status === 'locked') {
                $team->update(['status' => 'forming']);
            }
        });

        return back()->with('success', 'Feature 2: Team member removed and group status re-unlocked to forming.');
    }
}
