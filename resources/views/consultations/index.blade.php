@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Features 7 & 8: Consultation Slot Booking & Meeting Minutes Tracker</h2>
<p class="text-sm text-slate-500 mb-6">Book conflict-free supervisor consultation slots and track individual follow-up action items separately.</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    @if(in_array(auth()->user()->role ?? '', ['supervisor', 'coordinator'], true))
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Publish Consultation Slot (Supervisor / Coordinator)</h3>
        <form method="POST" action="{{ route('consultations.slots.store') }}" class="space-y-3 text-sm">
            @csrf
            <input type="hidden" name="supervisor_id" value="{{ auth()->id() }}">
            <div class="grid grid-cols-3 gap-2">
                <input type="date" name="slot_date" value="2026-10-26" required class="border rounded px-2 py-1.5">
                <input type="time" name="start_time" value="14:00" required class="border rounded px-2 py-1.5">
                <input type="time" name="end_time" value="14:30" required class="border rounded px-2 py-1.5">
            </div>
            <input type="text" name="room_or_meet_link" placeholder="UB40201 or Google Meet Link" required class="w-full border rounded px-3 py-1.5">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded font-semibold">Publish Slot</button>
        </form>
    </div>
    @endif

    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Log Meeting Minutes & Follow-Up Action Items (Feature 8)</h3>
        <form method="POST" action="{{ route('consultations.minutes.store') }}" class="space-y-3 text-sm">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <select name="team_id" class="border rounded px-3 py-1.5" required>
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                    @endforeach
                </select>
                <select name="slot_id" class="border rounded px-3 py-1.5" required>
                    @foreach($slots as $s)
                        <option value="{{ $s->id }}">{{ $s->slot_date }} ({{ $s->start_time }}) - {{ $s->room_or_meet_link }}</option>
                    @endforeach
                </select>
            </div>
            <input type="text" name="discussion_summary" placeholder="Reviewed UML Class Diagram and MVC separation..." required class="w-full border rounded px-3 py-1.5">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Enter one or more follow-up action items (separate items with a new line or `;` to track each item individually)</label>
                <textarea name="action_items" rows="2" placeholder="Refine Service layer before Sprint 1 deadline; Add unit tests for Jaccard similarity" required class="w-full border rounded px-3 py-1.5"></textarea>
            </div>
            <div class="grid grid-cols-3 gap-2 text-xs">
                <div>
                    <label class="block text-slate-500 mb-1">Assign Owner</label>
                    <select name="assigned_to" class="w-full border rounded px-2 py-1.5">
                        <option value="">Whole Team</option>
                        @foreach($students ?? [] as $stu)
                            <option value="{{ $stu->id }}">{{ $stu->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+5 days')) }}" class="w-full border rounded px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Initial Status</label>
                    <select name="action_status" class="w-full border rounded px-2 py-1.5">
                        <option value="pending">Pending</option>
                        <option value="in_progress" selected>In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full bg-emerald-600 text-white px-4 py-2 rounded font-semibold">Record Minutes & Action Items</button>
        </form>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-sm border">
    <h3 class="font-bold mb-4">Scheduled Consultations & Individual Action Item Tracker</h3>
    <div class="space-y-3 text-sm">
        @foreach($slots as $slot)
        <div class="p-4 border rounded-lg">
            <div class="flex justify-between items-center">
                <div>
                    <strong>{{ $slot->slot_date }} ({{ $slot->start_time }} - {{ $slot->end_time }}) • {{ $slot->room_or_meet_link }}</strong>
                    @if($slot->bookedTeam)
                        <span class="ml-2 text-xs text-indigo-700 font-semibold">Booked by: {{ $slot->bookedTeam->group_name }} (Agenda: {{ $slot->agenda }})</span>
                    @endif
                </div>
                <span class="uppercase text-xs font-bold px-2 py-0.5 rounded bg-slate-100">{{ $slot->status }}</span>
            </div>
            @if($slot->status === 'available' && (auth()->user()->role ?? '') === 'student')
            <form method="POST" action="{{ route('consultations.slots.book', $slot) }}" class="mt-2 pt-2 border-t flex space-x-2 text-xs">
                @csrf
                <select name="booked_by_team_id" class="border rounded px-2 py-1">
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                    @endforeach
                </select>
                <input type="text" name="agenda" placeholder="Discussion agenda (e.g. Sprint 2 MVC review)..." required class="flex-1 border rounded px-2 py-1">
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1 rounded font-semibold">Book Slot (F7)</button>
            </form>
            @endif
            @if($slot->status === 'booked')
            <div class="mt-2 pt-2 border-t flex justify-end">
                <form method="POST" action="{{ route('consultations.slots.cancel', $slot) }}">
                    @csrf
                    <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-semibold border border-rose-200 px-2.5 py-1 rounded bg-rose-50">
                        Cancel Booking & Reopen Slot
                    </button>
                </form>
            </div>
            @endif
            @foreach($slot->minutes as $min)
                @php
                    $isOverdue = $min->due_date && $min->due_date < date('Y-m-d') && $min->action_status !== 'resolved';
                @endphp
                <div class="mt-2 p-2.5 {{ $isOverdue ? 'bg-rose-50 border-rose-300' : 'bg-slate-50' }} rounded text-xs flex justify-between items-center border">
                    <div>
                        <strong>Summary:</strong> {{ $min->discussion_summary }} |
                        <strong>Action Item #{{ $min->id }}:</strong> {{ $min->action_items }}
                        @if($min->assignee)
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-semibold">Owner: {{ $min->assignee->name }}</span>
                        @endif
                        @if($min->due_date)
                            <span class="ml-1 px-1.5 py-0.5 rounded {{ $isOverdue ? 'bg-rose-600 text-white font-bold' : 'bg-slate-200 text-slate-700' }}">
                                Due: {{ $min->due_date }} {{ $isOverdue ? '(OVERDUE)' : '' }}
                            </span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('consultations.minutes.status', $min) }}" class="flex items-center space-x-1">
                        @csrf
                        <select name="action_status" class="border rounded px-1.5 py-0.5 text-xs font-semibold">
                            <option value="pending" @selected($min->action_status === 'pending')>Pending</option>
                            <option value="in_progress" @selected($min->action_status === 'in_progress')>In Progress</option>
                            <option value="resolved" @selected($min->action_status === 'resolved')>Resolved</option>
                        </select>
                        <button type="submit" class="bg-slate-800 text-white px-2 py-0.5 rounded text-xs">Update</button>
                    </form>
                </div>
            @endforeach
        </div>
        @endforeach
    </div>
</div>
@endsection
