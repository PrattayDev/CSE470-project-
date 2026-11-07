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
            <input type="date" name="defense_date" value="2026-12-15" required class="w-full border rounded px-3 py-1.5">
            <div class="grid grid-cols-2 gap-2">
                <input type="time" name="start_time" value="10:00" required class="border rounded px-2 py-1.5">
                <input type="time" name="end_time" value="10:40" required class="border rounded px-2 py-1.5">
            </div>
            <input type="text" name="room_number" placeholder="Room UB40102" required class="w-full border rounded px-3 py-1.5">
            <input type="text" name="external_examiner_name" placeholder="Dr. External Reviewer" required class="w-full border rounded px-3 py-1.5">
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded font-semibold">Check Conflict & Schedule</button>
        </form>
    </div>
    @endif
    <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Scheduled Defense Panels</h3>
        @foreach($schedules as $sc)
            <div class="p-3 border rounded mb-2 text-sm flex justify-between items-center">
                <div>
                    <strong>{{ $sc->team->group_name ?? 'Team' }}</strong> • {{ $sc->defense_date }} ({{ $sc->start_time }} - {{ $sc->end_time }})
                    <div class="text-xs text-slate-500">Panel Chair: {{ $sc->panelChair->name ?? 'Faculty' }} • Status: <span class="uppercase font-bold">{{ $sc->status }}</span></div>
                </div>
                <div class="text-indigo-700 font-semibold">{{ $sc->room_number }} • External: {{ $sc->external_examiner_name }}</div>
            </div>
        @endforeach
    </div>
</div>
@endsection
