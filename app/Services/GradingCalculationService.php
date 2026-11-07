<?php

namespace App\Services;

use App\Models\PeerEvaluation;
use App\Models\TeamMember;

/**
 * Features 10 & 11: CSE 470 15-Mark Rubric Calculator & Peer Contribution Weightage Engine
 * Rubric Breakdown per CSE 470 Rule Book Section 15:
 * - SRS + Sprint Process & Version Control: Max 2.00 marks
 * - Implementation / MVC / Project Quality: Max 3.00 marks
 * - Individual Project Viva: Max 10.00 marks
 * Total = 15.00 marks
 */
class GradingCalculationService
{
    public function calculateRubricTotal(float $srsMarks, float $mvcMarks, float $vivaMarks): float
    {
        $srs = min(2.00, max(0.00, $srsMarks));
        $mvc = min(3.00, max(0.00, $mvcMarks));
        $viva = min(10.00, max(0.00, $vivaMarks));

        return round($srs + $mvc + $viva, 2);
    }

    public function calculatePeerWeightageForStudent(?int $teamId, int $studentId): array
    {
        $resolvedTeamId = $teamId ?? TeamMember::where('student_id', $studentId)->value('team_id');

        $reviews = PeerEvaluation::query()
            ->when($resolvedTeamId, fn($q) => $q->where('team_id', $resolvedTeamId))
            ->where('evaluatee_student_id', $studentId)
            ->get();

        // Students without peer reviews default to 0.0% (not 100%) until evaluated
        if ($reviews->isEmpty()) {
            return [
                'average_score_out_of_30' => 0.0,
                'contribution_percentage' => 0.0,
                'normalized_team_share' => 0.0,
                'review_count' => 0,
            ];
        }

        $studentAvgOut30 = $this->averageScoreOut30($reviews);

        // Normalize against all evaluated teammates in the same team
        $teamAllReviews = PeerEvaluation::query()
            ->when($resolvedTeamId, fn($q) => $q->where('team_id', $resolvedTeamId))
            ->get()
            ->groupBy('evaluatee_student_id');

        $sumOfMemberAverages = 0.0;
        $maxMemberAverage = 0.0;
        foreach ($teamAllReviews as $memberId => $memberReviews) {
            $avg = $this->averageScoreOut30($memberReviews);
            $sumOfMemberAverages += $avg;
            if ($avg > $maxMemberAverage) {
                $maxMemberAverage = $avg;
            }
        }

        // Normalized relative to the highest/mean teammate score (so equal top contributors get 100%, lower contributors get proportional weightage)
        $normalizedWeightage = $maxMemberAverage > 0
            ? round(($studentAvgOut30 / $maxMemberAverage) * 100, 1)
            : 0.0;

        $normalizedShare = $sumOfMemberAverages > 0
            ? round(($studentAvgOut30 / $sumOfMemberAverages) * 100, 1)
            : 0.0;

        return [
            'average_score_out_of_30' => round($studentAvgOut30, 2),
            'contribution_percentage' => $normalizedWeightage,
            'normalized_team_share' => $normalizedShare,
            'review_count' => $reviews->count(),
        ];
    }

    private function averageScoreOut30($reviews): float
    {
        if ($reviews->isEmpty()) {
            return 0.0;
        }
        $totalPoints = 0;
        foreach ($reviews as $review) {
            $totalPoints += (
                (int) $review->coding_contribution_score +
                (int) $review->sprint_reliability_score +
                (int) $review->documentation_score
            );
        }
        return $totalPoints / $reviews->count();
    }
}
