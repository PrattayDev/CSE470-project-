@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Feature 14: Defense / Viva Room & Panel Scheduling Engine</h2>
<p class="text-sm text-slate-500 mb-6">Conflict-free scheduling of 10-minute individual student vivas across rooms and examiner panels (validates time ranges & active schedules).</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    @if(in_array(auth()->user()->role ?? '', ['supervisor', 'coordinator'], true))
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Schedule Viva Defense Slot</h3>
        <form method="POST" action="{{ route('defense.store') }}" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block text-xs font-medium mb-1">Select Group</label>
                <select name="team_id" class="w-full border rounded px-3 py-1.5">
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Panel Chair (Faculty)</label>
                <select name="panel_chair_id" class="w-full border rounded px-3 py-1.5">
                    @foreach($supervisors as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Defense Date</label>
                <input type="date" name="defense_date" value="2026-12-15" required class="w-full border rounded px-3 py-2">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium mb-1">Start Time</label>
                    <input type="time" name="start_time" value="10:00" required class="w-full border rounded px-2 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">End Time</label>
                    <input type="time" name="end_time" value="10:40" required class="w-full border rounded px-2 py-2">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Room Number</label>
                <input type="text" name="room_number" placeholder="Room UB40102" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">External Examiner Name</label>
                <input type="text" name="external_examiner_name" placeholder="Dr. External Reviewer" required class="w-full border rounded px-3 py-2">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded font-semibold">Check Conflict & Schedule</button>
        </form>
    </div>
    @endif
    <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h3 class="font-bold">Scheduled Defense Panels</h3>
            <form method="GET" action="{{ route('defense.index') }}" class="flex flex-wrap gap-2 text-xs">
                <input type="date" name="date" value="{{ $dateFilter ?? '' }}" aria-label="Defense date filter" class="border rounded px-2.5 py-1.5">
                <input type="text" name="room" value="{{ $roomFilter ?? '' }}" placeholder="Room (e.g. UB40102)" class="border rounded px-2.5 py-1.5 w-36">
                <select name="status" aria-label="Defense status filter" class="border rounded px-2.5 py-1.5 bg-white">
                    <option value="">All Statuses</option>
                    <option value="scheduled" @selected(($statusFilter ?? '') === 'scheduled')>Scheduled</option>
                    <option value="completed" @selected(($statusFilter ?? '') === 'completed')>Completed</option>
                </select>
                <button type="submit" class="bg-slate-800 text-white px-3 py-1.5 rounded font-semibold">Filter</button>
                <a href="{{ route('defense.index') }}" class="px-2.5 py-1.5 border rounded text-slate-600 hover:bg-slate-100">Reset</a>
            </form>
        </div>
        @forelse($schedules as $sc)
            <div class="p-3.5 border rounded-lg mb-2 text-sm flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                <div>
                    <div class="flex items-center gap-2">
                        <strong>{{ $sc->team->group_name ?? 'Team' }}</strong>
                        <x-status-badge :status="$sc->status" />
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">{{ $sc->defense_date }} ({{ $sc->start_time }} - {{ $sc->end_time }}) • Panel Chair: {{ $sc->panelChair->name ?? 'Faculty' }}</div>
                </div>
                <div class="text-indigo-700 font-semibold text-xs sm:text-sm">{{ $sc->room_number }} • External: {{ $sc->external_examiner_name }}</div>
            </div>
        @empty
            <p class="text-xs text-slate-500">No defense schedules match the selected filter.</p>
        @endforelse
    </div>
</div>
@endsection
