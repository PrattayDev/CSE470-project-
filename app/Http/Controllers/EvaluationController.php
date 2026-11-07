<?php

namespace App\Http\Controllers;

use App\Models\PeerEvaluation;
use App\Models\RubricEvaluation;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\GradingCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Feature 10: Rubric-Based Evaluation & Individual Viva Grading System (15 Marks: 2 + 3 + 10)
 * Feature 11: Peer Evaluation & Contribution Weightage Calculator
 */
class EvaluationController extends Controller
{
    public function __construct(private GradingCalculationService $gradingService)
    {
    }

    public function index()
    {
        $rubrics = RubricEvaluation::with(['team', 'student', 'evaluator'])->get();
        $peerReviews = PeerEvaluation::with(['evaluator', 'evaluatee'])->latest()->get();
        $teams = Team::all();
        $students = User::where('role', 'student')->get();

        $weightageMap = [];
        foreach ($students as $student) {
            $weightageMap[$student->id] = $this->gradingService->calculatePeerWeightageForStudent(null, $student->id);
        }

        return view('evaluations.index', compact('rubrics', 'peerReviews', 'teams', 'students', 'weightageMap'));
    }

    public function storeRubric(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['supervisor', 'coordinator'], true)) {
            abort(403, 'Only supervisors or coordinators can submit rubric grades.');
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'student_id' => 'required|exists:users,id',
            'srs_and_sprint_marks' => 'required|numeric|between:0,2.00',
            'mvc_implementation_marks' => 'required|numeric|between:0,3.00',
            'individual_viva_marks' => 'required|numeric|between:0,10.00',
            'viva_remarks' => 'nullable|string',
        ]);

        $student = User::findOrFail($validated['student_id']);
        if ($student->role !== 'student') {
            return back()->with('error', 'Feature 10 Rejected: Rubric grades can only be recorded for student accounts.');
        }

        $inTeam = TeamMember::where('team_id', $validated['team_id'])
            ->where('student_id', $student->id)
            ->exists();

        if (!$inTeam) {
            return back()->with('error', 'Feature 10 Rejected: Selected student does not belong to the specified project team.');
        }

        $existingRubric = RubricEvaluation::where('team_id', $validated['team_id'])
            ->where('student_id', $validated['student_id'])
            ->first();

        if ($existingRubric && $existingRubric->is_finalized) {
            return back()->with('error', 'Feature 10 Rejected: This student\'s rubric grade has been finalized by the Coordinator and is locked against edits.');
        }

        $total = $this->gradingService->calculateRubricTotal(
            (float) $validated['srs_and_sprint_marks'],
            (float) $validated['mvc_implementation_marks'],
            (float) $validated['individual_viva_marks']
        );

        RubricEvaluation::updateOrCreate(
            ['team_id' => $validated['team_id'], 'student_id' => $validated['student_id']],
            [
                'evaluated_by' => $user->id,
                'srs_and_sprint_marks' => $validated['srs_and_sprint_marks'],
                'mvc_implementation_marks' => $validated['mvc_implementation_marks'],
                'individual_viva_marks' => $validated['individual_viva_marks'],
                'total_marks' => $total,
                'viva_remarks' => $validated['viva_remarks'],
            ]
        );

        return back()->with('success', "Feature 10: Individual CSE 470 Rubric Grade saved (Total: {$total} / 15.00).");
    }

    public function finalizeRubric(Request $request, RubricEvaluation $rubric)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'coordinator') {
            abort(403, 'Only Course Coordinators can finalize and lock rubric grades.');
        }

        $rubric->update(['is_finalized' => true]);

        return back()->with('success', 'Feature 10: Rubric grade finalized and locked by Coordinator.');
    }

    public function storePeerEvaluation(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'student') {
            abort(403, 'Only students can submit peer contribution evaluations.');
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'evaluatee_student_id' => 'required|exists:users,id',
            'coding_contribution_score' => 'required|integer|between:1,10',
            'sprint_reliability_score' => 'required|integer|between:1,10',
            'documentation_score' => 'required|integer|between:1,10',
            'justification' => 'required|string',
        ]);

        if ((int) $validated['evaluatee_student_id'] === (int) $user->id) {
            return back()->with('error', 'Feature 11 Rejected: You cannot submit a peer evaluation for yourself.');
        }

        $evaluatorInTeam = TeamMember::where('team_id', $validated['team_id'])
            ->where('student_id', $user->id)
            ->exists();

        $evaluateeInTeam = TeamMember::where('team_id', $validated['team_id'])
            ->where('student_id', $validated['evaluatee_student_id'])
            ->exists();

        if (!$evaluatorInTeam || !$evaluateeInTeam) {
            return back()->with('error', 'Feature 11 Rejected: Both evaluator and evaluatee must be members of the same project team.');
        }

        PeerEvaluation::updateOrCreate(
            [
                'team_id' => $validated['team_id'],
                'evaluator_student_id' => $user->id,
                'evaluatee_student_id' => $validated['evaluatee_student_id'],
            ],
            [
                'coding_contribution_score' => $validated['coding_contribution_score'],
                'sprint_reliability_score' => $validated['sprint_reliability_score'],
                'documentation_score' => $validated['documentation_score'],
                'justification' => $validated['justification'],
            ]
        );

        return back()->with('success', 'Feature 11: Peer contribution evaluation submitted and team-normalized weightage updated.');
    }
}
