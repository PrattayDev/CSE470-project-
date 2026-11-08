<?php

namespace App\Http\Controllers;

use App\Models\DefenseSchedule;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Feature 14: Defense/Viva Room & Panel Scheduling Engine
 */
class DefenseScheduleController extends Controller
{
    public function index(Request $request)
    {
        $dateFilter = trim((string) $request->query('date', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $roomFilter = trim((string) $request->query('room', ''));

        $schedules = DefenseSchedule::with(['team', 'panelChair'])
            ->when($dateFilter !== '', fn ($q) => $q->whereDate('defense_date', $dateFilter))
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->when($roomFilter !== '', fn ($q) => $q->where('room_number', 'LIKE', "%{$roomFilter}%"))
            ->orderBy('defense_date')
            ->orderBy('start_time')
            ->get();

        $teams = Team::all();
        $supervisors = User::whereIn('role', ['supervisor', 'coordinator'])->get();

        return view('defense.index', compact('schedules', 'teams', 'supervisors', 'dateFilter', 'statusFilter', 'roomFilter'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['supervisor', 'coordinator'], true)) {
            abort(403, 'Only supervisors or coordinators can schedule defense panels.');
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'defense_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'room_number' => 'required|string|max:60',
            'panel_chair_id' => 'required|exists:users,id',
            'external_examiner_name' => 'required|string|max:120',
        ]);

        if (strtotime($validated['end_time']) <= strtotime($validated['start_time'])) {
            return back()->with('error', 'Feature 14 Rejected: Defense slot end time must be strictly after start time.');
        }

        // Conflict Detection Logic: Active ('scheduled') defense slots block room, panel chair, team, or external examiner
        $conflict = DefenseSchedule::where('defense_date', $validated['defense_date'])
            ->where('status', 'scheduled')
            ->where(function ($q) use ($validated) {
                $q->where('room_number', $validated['room_number'])
                  ->orWhere('panel_chair_id', $validated['panel_chair_id'])
                  ->orWhere('team_id', $validated['team_id'])
                  ->orWhere('external_examiner_name', $validated['external_examiner_name']);
            })
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($conflict) {
            return back()->with('error', 'Feature 14 Conflict Detected: The selected Room, Panel Chair, Project Team, or External Examiner is already scheduled during this time window!');
        }

        DefenseSchedule::create([
            ...$validated,
            'status' => 'scheduled',
        ]);

        return back()->with('success', 'Feature 14: Conflict-free Viva Defense slot scheduled (10 mins per student).');
    }
}
