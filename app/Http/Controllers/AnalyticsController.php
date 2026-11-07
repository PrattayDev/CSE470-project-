<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Proposal;
use App\Models\Sprint;
use App\Models\SprintTask;
use App\Models\RubricEvaluation;
use App\Models\EquipmentRequisition;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Feature 20: Coordinator Analytics & Cohort Performance Dashboard (Prattay)
 */
class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null && $user->role === 'coordinator', 403);

        $sectionFilter = trim((string) $request->query('section', ''));

        $teamsQuery = Team::query()->when($sectionFilter !== '', fn ($q) => $q->where('section', $sectionFilter));
        $filteredTeamIds = $teamsQuery->pluck('id')->toArray();

        // Exclude unstarted ('planned') sprints so attainment reflects active/completed sprint performance
        $activeSprintsQuery = Sprint::whereIn('status', ['in_progress', 'submitted', 'reviewed'])
            ->when($sectionFilter !== '', fn ($q) => $q->whereIn('team_id', $filteredTeamIds));
        $activeSprintIds = $activeSprintsQuery->pluck('id')->toArray();
        $activeSprintCount = count($activeSprintIds);

        $completedTasksInActiveSprints = $activeSprintCount > 0
            ? SprintTask::whereIn('sprint_id', $activeSprintIds)->where('status', 'completed')->count()
            : 0;

        $avgSprintVelocity = $activeSprintCount > 0
            ? round($completedTasksInActiveSprints / $activeSprintCount, 2)
            : 0.0;

        $avgRubricScore = round((float) RubricEvaluation::when($sectionFilter !== '', fn ($q) => $q->whereIn('team_id', $filteredTeamIds))->avg('total_marks'), 2);

        $metrics = [
            'total_students' => User::where('role', 'student')->when($sectionFilter !== '', fn ($q) => $q->where('section', $sectionFilter))->count(),
            'total_teams' => count($filteredTeamIds),
            'approved_proposals' => Proposal::where('status', 'approved')->when($sectionFilter !== '', fn ($q) => $q->whereIn('team_id', $filteredTeamIds))->count(),
            'avg_sprint_attainment' => round((float) Sprint::whereIn('status', ['in_progress', 'submitted', 'reviewed'])->when($sectionFilter !== '', fn ($q) => $q->whereIn('team_id', $filteredTeamIds))->avg('attainment_percentage'), 1),
            'avg_sprint_velocity' => $avgSprintVelocity,
            'avg_rubric_score' => $avgRubricScore,
            'avg_viva_total' => $avgRubricScore,
            'active_lab_loans' => EquipmentRequisition::whereIn('status', ['issued', 'overdue'])->when($sectionFilter !== '', fn ($q) => $q->whereIn('team_id', $filteredTeamIds))->count(),
        ];

        $sprints = Sprint::with(['team', 'tasks'])
            ->when($sectionFilter !== '', fn ($q) => $q->whereIn('team_id', $filteredTeamIds))
            ->orderBy('sprint_number')
            ->get();
        $proposals = Proposal::with('team')
            ->when($sectionFilter !== '', fn ($q) => $q->whereIn('team_id', $filteredTeamIds))
            ->get();

        $loanStatusCounts = [
            'requested' => EquipmentRequisition::where('status', 'requested')->count(),
            'issued' => EquipmentRequisition::where('status', 'issued')->count(),
            'returned' => EquipmentRequisition::where('status', 'returned')->count(),
            'overdue' => EquipmentRequisition::where('status', 'overdue')->count(),
        ];

        return view('analytics.index', compact('metrics', 'sprints', 'proposals', 'sectionFilter', 'loanStatusCounts'));
    }
}
