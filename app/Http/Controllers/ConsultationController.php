<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSlot;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = trim((string) $request->query('status', ''));
        $dateFilter = trim((string) $request->query('date', ''));
        $teamFilter = trim((string) $request->query('team_id', ''));

        $slots = ConsultationSlot::with(['supervisor', 'bookedTeam'])
            ->when($statusFilter !== '', fn ($query) => $query->where('status', $statusFilter))
            ->when($dateFilter !== '', fn ($query) => $query->whereDate('slot_date', $dateFilter))
            ->when($teamFilter !== '', fn ($query) => $query->where('booked_by_team_id', (int) $teamFilter))
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        $supervisors = User::where('role', 'supervisor')->orderBy('name')->get();
        $teams = Team::orderBy('group_name')->get();

        return view('consultations.index', compact(
            'slots',
            'supervisors',
            'teams',
            'statusFilter',
            'dateFilter',
            'teamFilter'
        ));
    }

    public function storeSlot(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['supervisor', 'coordinator'], true)) {
            abort(403, 'Only supervisors or coordinators can publish consultation slots.');
        }

        $validated = $request->validate([
            'supervisor_id' => 'nullable|exists:users,id',
            'slot_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'room_or_meet_link' => 'required|string|max:200',
        ]);

        $supervisorId = $user->role === 'coordinator' && !empty($validated['supervisor_id'])
            ? (int) $validated['supervisor_id']
            : (int) $user->id;

        if (strtotime($validated['end_time']) <= strtotime($validated['start_time'])) {
            return back()->with('error', 'Consultation slot end time must be after its start time.');
        }

        $overlap = ConsultationSlot::where('slot_date', $validated['slot_date'])
            ->where(function ($query) use ($supervisorId, $validated) {
                $query->where('supervisor_id', $supervisorId)
                    ->orWhere('room_or_meet_link', $validated['room_or_meet_link']);
            })
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($overlap) {
            return back()->with('error', 'An overlapping slot already exists for this supervisor or room/link.');
        }

        ConsultationSlot::create([
            'supervisor_id' => $supervisorId,
            'slot_date' => $validated['slot_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room_or_meet_link' => $validated['room_or_meet_link'],
            'status' => 'available',
        ]);

        return back()->with('success', 'Conflict-free consultation slot published.');
    }

    public function bookSlot(Request $request, ConsultationSlot $slot)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'student') {
            abort(403, 'Only students can book consultation slots.');
        }

        $validated = $request->validate([
            'booked_by_team_id' => 'required|exists:teams,id',
            'agenda' => 'required|string|max:255',
        ]);

        $isMember = TeamMember::where('team_id', $validated['booked_by_team_id'])
            ->where('student_id', $user->id)
            ->exists();

        if (!$isMember) {
            return back()->with('error', 'You can only book consultation slots for your own project team.');
        }

        return DB::transaction(function () use ($slot, $validated) {
            $lockedSlot = ConsultationSlot::whereKey($slot->id)->lockForUpdate()->firstOrFail();

            if ($lockedSlot->status !== 'available' || $lockedSlot->booked_by_team_id !== null) {
                return back()->with('error', 'This consultation slot has already been booked.');
            }

            $existingUpcomingWithSupervisor = ConsultationSlot::where('supervisor_id', $lockedSlot->supervisor_id)
                ->where('booked_by_team_id', $validated['booked_by_team_id'])
                ->where('status', 'booked')
                ->whereDate('slot_date', '>=', now()->toDateString())
                ->where('id', '!=', $lockedSlot->id)
                ->exists();

            if ($existingUpcomingWithSupervisor) {
                return back()->with('error', 'Your team already has an upcoming booking with this supervisor.');
            }

            $lockedSlot->update([
                'booked_by_team_id' => $validated['booked_by_team_id'],
                'agenda' => $validated['agenda'],
                'status' => 'booked',
            ]);

            return back()->with('success', 'Consultation slot booked for your team.');
        });
    }

    public function cancelBooking(Request $request, ConsultationSlot $slot)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if ($slot->status !== 'booked' || !$slot->booked_by_team_id) {
            return back()->with('error', 'Only currently booked consultation slots can be cancelled.');
        }

        if ($user->role === 'student') {
            $isMember = TeamMember::where('team_id', $slot->booked_by_team_id)
                ->where('student_id', $user->id)
                ->exists();
            if (!$isMember) {
                return back()->with('error', 'You can only cancel bookings for your own team.');
            }
        } elseif ($user->role === 'supervisor' && (int) $slot->supervisor_id !== (int) $user->id) {
            return back()->with('error', 'You can only cancel bookings on your own consultation slots.');
        }

        $slot->update([
            'booked_by_team_id' => null,
            'agenda' => null,
            'status' => 'available',
        ]);

        return back()->with('success', 'Consultation booking cancelled and the slot reopened.');
    }
}
