<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSlot;
use App\Models\MeetingMinute;
use App\Models\SystemNotification;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Feature 7: Consultation Slot Booking & Meeting Scheduler
 * Feature 8: Meeting Minutes & Action Item Tracker
 */
class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = trim((string) $request->query('status', ''));
        $dateFilter = trim((string) $request->query('date', ''));
        $teamFilter = trim((string) $request->query('team_id', ''));

        $slots = ConsultationSlot::with(['supervisor', 'bookedTeam', 'minutes.recorder', 'minutes.assignee'])
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->when($dateFilter !== '', fn ($q) => $q->whereDate('slot_date', $dateFilter))
            ->when($teamFilter !== '', fn ($q) => $q->where('booked_by_team_id', (int) $teamFilter))
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();
        $supervisors = User::whereIn('role', ['supervisor', 'coordinator'])->get();
        $teams = Team::all();
        $students = User::where('role', 'student')->orderBy('name')->get();

        return view('consultations.index', compact('slots', 'supervisors', 'teams', 'students', 'statusFilter', 'dateFilter', 'teamFilter'));
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
            return back()->with('error', 'Feature 7 Rejected: Consultation slot end time must be strictly after start time.');
        }

        $overlap = ConsultationSlot::where('slot_date', $validated['slot_date'])
            ->where(function ($q) use ($supervisorId, $validated) {
                $q->where('supervisor_id', $supervisorId)
                  ->orWhere('room_or_meet_link', $validated['room_or_meet_link']);
            })
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($overlap) {
            return back()->with('error', 'Feature 7 Conflict Detected: Overlapping consultation slot already exists for this supervisor or room/link.');
        }

        ConsultationSlot::create([
            'supervisor_id' => $supervisorId,
            'slot_date' => $validated['slot_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room_or_meet_link' => $validated['room_or_meet_link'],
            'status' => 'available',
        ]);

        return back()->with('success', 'Feature 7: Validated conflict-free supervisor consultation slot published.');
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
            return back()->with('error', 'Feature 7 Rejected: You can only book consultation slots for your own project team.');
        }

        return DB::transaction(function () use ($slot, $validated) {
            $lockedSlot = ConsultationSlot::where('id', $slot->id)->lockForUpdate()->firstOrFail();

            if ($lockedSlot->status !== 'available' || $lockedSlot->booked_by_team_id !== null) {
                return back()->with('error', 'Feature 7 Rejected: This consultation slot has already been booked by another team.');
            }

            $existingUpcomingWithSupervisor = ConsultationSlot::where('supervisor_id', $lockedSlot->supervisor_id)
                ->where('booked_by_team_id', $validated['booked_by_team_id'])
                ->where('status', 'booked')
                ->whereDate('slot_date', '>=', now()->toDateString())
                ->where('id', '!=', $lockedSlot->id)
                ->exists();

            if ($existingUpcomingWithSupervisor) {
                return back()->with('error', 'Feature 7 Rejected: Your team already holds an upcoming booked consultation slot with this supervisor.');
            }

            $lockedSlot->update([
                'booked_by_team_id' => $validated['booked_by_team_id'],
                'agenda' => $validated['agenda'],
                'status' => 'booked',
            ]);

            return back()->with('success', 'Feature 7: Consultation slot booked for your team.');
        });
    }

    public function cancelBooking(Request $request, ConsultationSlot $slot)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if ($slot->status !== 'booked' || !$slot->booked_by_team_id) {
            return back()->with('error', 'Feature 7 Rejected: Only currently booked consultation slots can be cancelled.');
        }

        if ($user->role === 'student') {
            $isMember = TeamMember::where('team_id', $slot->booked_by_team_id)
                ->where('student_id', $user->id)
                ->exists();
            if (!$isMember) {
                return back()->with('error', 'Feature 7 Rejected: You can only cancel consultation bookings for your own team.');
            }
        } elseif ($user->role === 'supervisor' && (int) $slot->supervisor_id !== (int) $user->id) {
            return back()->with('error', 'Feature 7 Rejected: You can only cancel bookings on your own consultation slots.');
        }

        $slot->update([
            'booked_by_team_id' => null,
            'agenda' => null,
            'status' => 'available',
        ]);

        return back()->with('success', 'Feature 7: Consultation booking cancelled and slot reopened as available.');
    }

    public function storeMinutes(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'slot_id' => 'required|exists:consultation_slots,id',
            'team_id' => 'required|exists:teams,id',
            'discussion_summary' => 'required|string',
            'action_items' => 'required|string',
            'action_status' => 'required|in:pending,in_progress,resolved',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $slot = ConsultationSlot::findOrFail($validated['slot_id']);
        $teamId = (int) ($slot->booked_by_team_id ?? $validated['team_id']);

        if ($user->role === 'student') {
            $isMember = TeamMember::where('team_id', $teamId)
                ->where('student_id', $user->id)
                ->exists();
            if (!$isMember) {
                return back()->with('error', 'Feature 8 Rejected: You can only record minutes for your own team consultation.');
            }
        }

        // Split multiline or semicolon-separated action items so each follow-up item can be tracked individually
        $rawItems = preg_split('/[\r\n;]+/', $validated['action_items']) ?: [$validated['action_items']];
        $items = array_values(array_filter(array_map('trim', $rawItems), fn ($item) => $item !== ''));
        if (empty($items)) {
            $items = [trim($validated['action_items'])];
        }

        foreach ($items as $itemText) {
            $minute = MeetingMinute::create([
                'slot_id' => $slot->id,
                'team_id' => $teamId,
                'discussion_summary' => $validated['discussion_summary'],
                'action_items' => $itemText,
                'action_status' => $validated['action_status'],
                'assigned_to' => $validated['assigned_to'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'recorded_by' => $user->id,
            ]);

            if (!empty($validated['due_date']) && $validated['due_date'] < now()->toDateString() && $validated['action_status'] !== 'resolved') {
                $targetUserId = (int) ($validated['assigned_to'] ?? $user->id);
                SystemNotification::create([
                    'user_id' => $targetUserId,
                    'notification_type' => 'deadline_reminder',
                    'title' => "Overdue Consultation Action Item (#{$minute->id})",
                    'message' => "Follow-up action item \"{$itemText}\" passed its due date ({$validated['due_date']}).",
                ]);
            }
        }

        $slot->update(['status' => 'completed']);

        return back()->with('success', 'Feature 8: Meeting minutes and ' . count($items) . ' individual follow-up action item(s) logged.');
    }

    public function updateMinuteStatus(Request $request, MeetingMinute $minute)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'action_status' => 'required|in:pending,in_progress,resolved',
        ]);

        if ($user->role === 'student') {
            $isMember = TeamMember::where('team_id', $minute->team_id)
                ->where('student_id', $user->id)
                ->exists();
            if (!$isMember) {
                return back()->with('error', 'Feature 8 Rejected: You can only update action items for your own team.');
            }
        }

        $minute->update([
            'action_status' => $validated['action_status'],
        ]);

        return back()->with('success', 'Feature 8: Follow-up action item status updated to ' . strtoupper($validated['action_status']) . '.');
    }
}
