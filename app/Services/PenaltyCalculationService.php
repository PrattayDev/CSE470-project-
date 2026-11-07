<?php

namespace App\Services;

use App\Models\EquipmentRequisition;
use App\Models\DocumentSubmission;
use Carbon\Carbon;

/**
 * Features 13 & 19: Lab Equipment Return Penalty & Automated Clearance Verification
 */
class PenaltyCalculationService
{
    public function computeLateFine(EquipmentRequisition $requisition, ?string $returnDate = null): float
    {
        $due = Carbon::parse($requisition->due_date)->startOfDay();
        $actualReturn = $returnDate
            ? Carbon::parse($returnDate)->startOfDay()
            : Carbon::now()->startOfDay();

        if ($actualReturn->lessThanOrEqualTo($due)) {
            return 0.00;
        }

        $lateDays = $due->diffInDays($actualReturn);
        $dailyRate = $requisition->equipment ? (float)$requisition->equipment->daily_late_fine_bdt : 50.00;

        return round($lateDays * $dailyRate * $requisition->quantity, 2);
    }

    public function verifyTeamClearanceEligibility(int $teamId): array
    {
        $unreturnedItems = EquipmentRequisition::where('team_id', $teamId)
            ->whereIn('status', ['issued', 'overdue'])
            ->count();

        $unpaidFines = (float) EquipmentRequisition::where('team_id', $teamId)
            ->sum('calculated_fine_bdt');

        $approvedSrs = DocumentSubmission::where('team_id', $teamId)
            ->where('document_type', 'SRS')
            ->where('faculty_review_status', 'approved')
            ->exists();

        // Require all 3 conditions: 0 unreturned items, 0 unpaid fines, and an approved SRS document
        $eligible = ($unreturnedItems === 0) && ($unpaidFines <= 0.0) && $approvedSrs;

        return [
            'is_eligible' => $eligible,
            'unreturned_equipment_count' => $unreturnedItems,
            'outstanding_fine_bdt' => number_format($unpaidFines, 2, '.', ''),
            'srs_approved' => $approvedSrs,
        ];
    }
}
