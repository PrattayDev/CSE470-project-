<?php

namespace App\Http\Controllers;

use App\Models\EquipmentRequisition;
use App\Models\LabEquipment;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\PenaltyCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Feature 12: Hardware & Lab Equipment Inventory Management
 * Feature 13: Lab Resource Requisition & Return Penalty Workflow
 */
class LabInventoryController extends Controller
{
    public function __construct(private PenaltyCalculationService $penaltyService)
    {
    }

    public function index()
    {
        $equipments = LabEquipment::orderBy('category')->get();
        $requisitions = EquipmentRequisition::with(['team', 'equipment', 'requester'])->latest()->get();
        $teams = Team::all();

        return view('inventory.index', compact('equipments', 'requisitions', 'teams'));
    }

    public function storeEquipment(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['lab_admin', 'coordinator'], true)) {
            abort(403, 'Only Lab Administrators or Coordinators can add hardware inventory.');
        }

        $validated = $request->validate([
            'asset_code' => 'required|string|max:50|unique:lab_equipments,asset_code',
            'equipment_name' => 'required|string|max:150',
            'category' => 'required|string|max:80',
            'total_quantity' => 'required|integer|min:1',
            'daily_late_fine_bdt' => 'required|numeric|min:0',
            'condition_status' => 'required|in:excellent,good,maintenance,damaged',
            'condition_notes' => 'nullable|string|max:500',
        ]);

        LabEquipment::create([
            ...$validated,
            'available_quantity' => $validated['total_quantity'],
        ]);

        return back()->with('success', 'Feature 12: Hardware asset added to lab inventory.');
    }

    public function updateEquipment(Request $request, LabEquipment $equipment)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['lab_admin', 'coordinator'], true)) {
            abort(403, 'Only Lab Administrators or Coordinators can update hardware inventory.');
        }

        $validated = $request->validate([
            'total_quantity' => 'required|integer|min:1',
            'available_quantity' => 'nullable|integer|min:0|lte:total_quantity',
            'daily_late_fine_bdt' => 'required|numeric|min:0',
            'condition_status' => 'required|in:excellent,good,maintenance,damaged',
            'condition_notes' => 'nullable|string|max:500',
        ]);

        if (!isset($validated['available_quantity']) || $validated['available_quantity'] === null) {
            $validated['available_quantity'] = max(0, (int) $validated['total_quantity'] - $equipment->activeLoanQuantity());
        }

        $equipment->update($validated);

        return back()->with('success', 'Feature 12: Hardware stock and condition updated with strict quantity bounds.');
    }

    public function requestEquipment(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'student') {
            abort(403, 'Only students can submit equipment requisitions.');
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'equipment_id' => 'required|exists:lab_equipments,id',
            'quantity' => 'required|integer|min:1',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
        ]);

        $isMember = TeamMember::where('team_id', $validated['team_id'])
            ->where('student_id', $user->id)
            ->exists();

        if (!$isMember) {
            return back()->with('error', 'Feature 13 Rejected: You can only request lab equipment for your own project team.');
        }

        $equipment = LabEquipment::findOrFail($validated['equipment_id']);
        if ($equipment->available_quantity < $validated['quantity']) {
            return back()->with('error', 'Insufficient lab stock available for this equipment.');
        }

        EquipmentRequisition::create([
            ...$validated,
            'requested_by' => $user->id,
            'status' => 'requested',
        ]);

        return back()->with('success', 'Feature 13: Lab equipment requisition submitted and awaiting Lab Admin approval.');
    }

    public function approveRequisition(Request $request, EquipmentRequisition $requisition)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['lab_admin', 'coordinator'], true)) {
            abort(403, 'Only Lab Administrators or Coordinators can approve and issue equipment.');
        }

        return DB::transaction(function () use ($requisition) {
            $lockedReq = EquipmentRequisition::where('id', $requisition->id)->lockForUpdate()->firstOrFail();

            if (!in_array($lockedReq->status, ['requested', 'approved'], true)) {
                return back()->with('error', 'Feature 13 Rejected: Only pending requisitions can be approved and issued.');
            }

            $equipment = LabEquipment::where('id', $lockedReq->equipment_id)->lockForUpdate()->firstOrFail();
            if ($equipment->available_quantity < $lockedReq->quantity) {
                return back()->with('error', 'Feature 13 Rejected: Insufficient available stock to issue this requisition.');
            }

            $equipment->decrement('available_quantity', $lockedReq->quantity);
            $lockedReq->update(['status' => 'issued']);

            return back()->with('success', 'Feature 13: Equipment requisition approved and issued; lab stock decremented.');
        });
    }

    public function returnEquipment(Request $request, EquipmentRequisition $requisition)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['lab_admin', 'coordinator'], true)) {
            abort(403, 'Only Lab Administrators or Coordinators can process equipment returns.');
        }

        $validated = $request->validate([
            'returned_date' => 'required|date|after_or_equal:' . $requisition->issue_date,
        ]);

        return DB::transaction(function () use ($requisition, $validated) {
            $lockedReq = EquipmentRequisition::where('id', $requisition->id)->lockForUpdate()->firstOrFail();

            if ($lockedReq->status === 'returned') {
                return back()->with('error', 'Feature 13 Rejected: This equipment requisition has already been returned.');
            }

            if (!in_array($lockedReq->status, ['issued', 'overdue'], true)) {
                return back()->with('error', 'Feature 13 Rejected: Only issued or overdue equipment can be returned.');
            }

            $returnDate = $validated['returned_date'];
            $fine = $this->penaltyService->computeLateFine($lockedReq, $returnDate);

            $lockedReq->update([
                'returned_date' => $returnDate,
                'calculated_fine_bdt' => $fine,
                'status' => 'returned',
            ]);

            $equipment = LabEquipment::where('id', $lockedReq->equipment_id)->lockForUpdate()->firstOrFail();
            $newAvailable = min($equipment->total_quantity, $equipment->available_quantity + $lockedReq->quantity);
            $equipment->update(['available_quantity' => $newAvailable]);

            return back()->with('success', "Feature 13: Equipment returned! Calculated Late Fine: {$fine} BDT.");
        });
    }

    public function payFine(Request $request, EquipmentRequisition $requisition)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['lab_admin', 'coordinator'], true)) {
            abort(403, 'Only Lab Administrators or Coordinators can settle overdue fines.');
        }

        if ($requisition->calculated_fine_bdt <= 0) {
            return back()->with('error', 'No outstanding fine exists on this requisition.');
        }

        $paidAmount = (float) $requisition->calculated_fine_bdt;
        $requisition->update([
            'fine_paid_amount' => ((float) $requisition->fine_paid_amount) + $paidAmount,
            'fine_paid_at' => now(),
            'calculated_fine_bdt' => 0.00,
        ]);

        return back()->with('success', "Feature 13: Overdue lab fine of {$paidAmount} BDT settled and recorded in payment ledger.");
    }
}
