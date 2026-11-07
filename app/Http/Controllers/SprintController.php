<?php

namespace App\Http\Controllers;

use App\Models\Sprint;
use App\Models\SprintTask;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Feature 5: Milestone & Weekly Sprint Planning Module
 * Feature 6: Sprint Attainment & Progress Review Workflow
 */
class SprintController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?? Auth::user();
        abort_unless($user !== null, 401);

        if ($user->role === 'student') {
            $teams = Team::whereHas('members', fn ($q) => $q->where('student_id', $user->id))
                ->orWhere('leader_id', $user->id)
                ->get();
            $teamIds = $teams->pluck('id')->toArray();

            $sprints = Sprint::with(['team', 'tasks.assignee'])
                ->whereIn('team_id', $teamIds)
                ->orderBy('team_id')
                ->orderBy('sprint_number')
                ->get();
            $memberStudentIds = TeamMember::whereIn('team_id', $teamIds)->pluck('student_id')->toArray();
            $students = User::where('role', 'student')->whereIn('id', $memberStudentIds)->get();
        } else {
            $teams = Team::all();
            $sprints = Sprint::with(['team', 'tasks.assignee'])->orderBy('team_id')->orderBy('sprint_number')->get();
            $students = User::where('role', 'student')->get();
        }

        return view('sprints.index', compact('sprints', 'teams', 'students'));
    }

    public function storeSprint(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'sprint_number' => 'required|integer|min:0|max:4',
            'sprint_goal' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($user->role === 'student') {
            $isMember = TeamMember::where('team_id', $validated['team_id'])
                ->where('student_id', $user->id)
                ->exists();
            if (!$isMember) {
                return back()->with('error', 'Feature 5 Rejected: You can only initialize sprints for your own team.');
            }
        }

        $duplicate = Sprint::where('team_id', $validated['team_id'])
            ->where('sprint_number', $validated['sprint_number'])
            ->exists();

        if ($duplicate) {
            return back()->with('error', 'Feature 5 Rejected: Sprint ' . $validated['sprint_number'] . ' already exists for this team.');
        }

        $dateOverlap = Sprint::where('team_id', $validated['team_id'])
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($dateOverlap) {
            return back()->with('error', 'Feature 5 Rejected: Sprint date range overlaps an existing sprint for this team.');
        }

        Sprint::create([
            ...$validated,
            'status' => 'planned',
            'attainment_percentage' => 0,
        ]);

        return back()->with('success', 'Feature 5: Weekly Sprint (Sprints 0–4) initialized with measurable goals.');
    }

    public function storeTask(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'sprint_id' => 'required|exists:sprints,id',
            'assigned_to' => 'required|exists:users,id',
            'feature_number' => 'required|integer|min:1|max:20',
            'feature_title' => 'required|string|max:200',
            'task_description' => 'required|string',
            'git_commit_hash' => ['nullable', 'string', 'max:80', 'regex:/^[0-9a-fA-F]{7,40}$/'],
            'status' => 'required|in:todo,in_progress,completed,carried_over',
        ]);

        $sprint = Sprint::findOrFail($validated['sprint_id']);
        if ($sprint->status === 'reviewed' && $user->role === 'student') {
            return back()->with('error', 'Feature 5 Rejected: This sprint has already been reviewed and locked by the supervisor.');
        }

        if ($user->role === 'student') {
            $isMember = TeamMember::where('team_id', $sprint->team_id)
                ->where('student_id', $user->id)
                ->exists();
            if (!$isMember) {
                return back()->with('error', 'Feature 5 Rejected: You can only add tasks to your own team sprint.');
            }
        }

        $assigneeInTeam = TeamMember::where('team_id', $sprint->team_id)
            ->where('student_id', $validated['assigned_to'])
            ->exists();

        if (!$assigneeInTeam) {
            return back()->with('error', 'Feature 5 Rejected: The assigned student must be a member of the sprint team.');
        }

        SprintTask::create($validated);
        $this->recalculateSprintAttainment((int) $validated['sprint_id']);

        return back()->with('success', 'Feature 5: Sprint task assigned and linked to Git activity.');
    }

    public function updateTaskStatus(Request $request, SprintTask $task)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,completed,carried_over',
            'git_commit_hash' => ['nullable', 'string', 'max:80', 'regex:/^[0-9a-fA-F]{7,40}$/'],
        ]);

        $sprint = Sprint::findOrFail($task->sprint_id);

        if ($sprint->status === 'reviewed' && $user->role === 'student') {
            return back()->with('error', 'Feature 6 Rejected: This sprint has been reviewed and locked by the supervisor.');
        }

        if ($user->role === 'student') {
            $isMember = TeamMember::where('team_id', $sprint->team_id)
                ->where('student_id', $user->id)
                ->exists();
            if (!$isMember) {
                return back()->with('error', 'Feature 6 Rejected: You can only update tasks belonging to your own team.');
            }
            if ((int) $task->assigned_to !== (int) $user->id) {
                return back()->with('error', 'Feature 6 Rejected: You can only update the status and commit hash of tasks assigned to you.');
            }
        }

        $task->update([
            'status' => $validated['status'],
            'git_commit_hash' => $validated['git_commit_hash'] ?? $task->git_commit_hash,
        ]);

        $this->recalculateSprintAttainment((int) $task->sprint_id);

        return back()->with('success', 'Feature 6: Task status updated and sprint attainment percentage recalculated.');
    }

    public function submitAttainment(Request $request, Sprint $sprint)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if ($sprint->status === 'reviewed' && $user->role === 'student') {
            return back()->with('error', 'Feature 6 Rejected: This sprint has already been reviewed and locked by the supervisor.');
        }

        if ($user->role === 'student') {
            $isMember = TeamMember::where('team_id', $sprint->team_id)
                ->where('student_id', $user->id)
                ->exists();
            if (!$isMember) {
                return back()->with('error', 'Feature 6 Rejected: You can only submit attainment reports for your own team.');
            }
        }

        $validated = $request->validate([
            'attainment_summary' => 'required|string',
            'blockers_reported' => 'nullable|string',
            'github_pr_or_commit_url' => 'required|url',
            'demo_video_url' => 'nullable|url',
            'supervisor_remarks' => 'nullable|string',
            'status' => 'required|in:in_progress,submitted,reviewed',
        ]);

        if ($user->role === 'student') {
            unset($validated['supervisor_remarks']);
            if ($validated['status'] === 'reviewed') {
                $validated['status'] = 'submitted';
            }
        }

        $sprint->update($validated);
        $this->recalculateSprintAttainment($sprint->id);

        return back()->with('success', 'Feature 6: Friday Sprint Review & Attainment report updated.');
    }

    /**
     * Feature 6 (Defend): Dedicated Supervisor Sprint Review & Lock Workflow
     */
    public function reviewSprint(Request $request, Sprint $sprint)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['supervisor', 'coordinator'], true)) {
            abort(403, 'Only supervisors or coordinators can review and lock sprint attainment reports.');
        }

        $validated = $request->validate([
            'supervisor_remarks' => 'required|string|max:500',
            'status' => 'required|in:reviewed,in_progress',
        ]);

        $sprint->update([
            'supervisor_remarks' => $validated['supervisor_remarks'],
            'status' => $validated['status'],
        ]);

        $this->recalculateSprintAttainment($sprint->id);

        $msg = $validated['status'] === 'reviewed'
            ? "Feature 6: Sprint {$sprint->sprint_number} attainment accepted and locked as REVIEWED."
            : "Feature 6: Sprint {$sprint->sprint_number} returned to IN_PROGRESS for team follow-up.";

        return back()->with('success', $msg);
    }

    private function recalculateSprintAttainment(int $sprintId): void
    {
        $sprint = Sprint::with('tasks')->find($sprintId);
        if (!$sprint) {
            return;
        }
        if ($sprint->tasks->isEmpty()) {
            $sprint->update(['attainment_percentage' => 0]);
            return;
        }
        $completed = $sprint->tasks->where('status', 'completed')->count();
        $percentage = (int) round(($completed / $sprint->tasks->count()) * 100);
        $sprint->update(['attainment_percentage' => $percentage]);
    }
}
