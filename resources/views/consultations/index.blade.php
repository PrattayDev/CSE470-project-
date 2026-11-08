@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Feature 7: Consultation Slot Booking</h1>
    <p class="mt-1 text-sm text-slate-600">
        Publish conflict-free supervisor slots, book a consultation for your team, or cancel a booking.
    </p>
</div>

@if(in_array(auth()->user()->role, ['supervisor', 'coordinator'], true))
    <section class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
        <h2 class="mb-4 font-bold">Publish a consultation slot</h2>
        <form method="POST" action="{{ route('consultations.slots.store') }}" class="space-y-4 text-sm">
            @csrf

            @if(auth()->user()->role === 'coordinator')
                <div>
                    <label for="supervisor_id" class="mb-1 block text-xs font-medium text-slate-600">Supervisor</label>
                    <select name="supervisor_id" id="supervisor_id" required class="w-full rounded border bg-white px-3 py-2">
                        <option value="">Choose a supervisor</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}" @selected(old('supervisor_id') == $supervisor->id)>
                                {{ $supervisor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="supervisor_id" value="{{ auth()->id() }}">
            @endif

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label for="slot_date" class="mb-1 block text-xs font-medium text-slate-600">Date</label>
                    <input type="date" id="slot_date" name="slot_date" value="{{ old('slot_date', date('Y-m-d')) }}"
                           min="{{ date('Y-m-d') }}" required class="w-full rounded border px-3 py-2">
                </div>
                <div>
                    <label for="start_time" class="mb-1 block text-xs font-medium text-slate-600">Start time</label>
                    <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '14:00') }}"
                           required class="w-full rounded border px-3 py-2">
                </div>
                <div>
                    <label for="end_time" class="mb-1 block text-xs font-medium text-slate-600">End time</label>
                    <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '14:30') }}"
                           required class="w-full rounded border px-3 py-2">
                </div>
            </div>

            <div>
                <label for="room_or_meet_link" class="mb-1 block text-xs font-medium text-slate-600">Room or meeting link</label>
                <input type="text" id="room_or_meet_link" name="room_or_meet_link" value="{{ old('room_or_meet_link') }}"
                       placeholder="Room number or meeting URL" required maxlength="200" class="w-full rounded border px-3 py-2">
            </div>

            <button type="submit" class="rounded bg-indigo-600 px-4 py-2.5 font-semibold text-white hover:bg-indigo-700">
                Publish slot
            </button>
        </form>
    </section>
@endif

<section class="rounded-xl border bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="font-bold">Consultation slots</h2>
        <form method="GET" action="{{ route('consultations.index') }}" class="flex flex-wrap gap-2 text-xs">
            <input type="date" name="date" value="{{ $dateFilter }}" aria-label="Filter by date"
                   class="rounded border px-2.5 py-1.5">
            <select name="status" aria-label="Filter by status" class="rounded border bg-white px-2.5 py-1.5">
                <option value="">All statuses</option>
                @foreach(['available' => 'Available', 'booked' => 'Booked'] as $value => $label)
                    <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded bg-slate-800 px-3 py-1.5 font-semibold text-white hover:bg-slate-700">Filter</button>
            <a href="{{ route('consultations.index') }}" class="rounded border px-2.5 py-1.5 text-slate-600 hover:bg-slate-100">Reset</a>
        </form>
    </div>

    @forelse($slots as $slot)
        @php
            $nextAction = $slot->status === 'available' ? 'Open for booking' : ($slot->status === 'booked' ? 'Booking active' : null);
        @endphp
        <article class="mb-3 rounded-lg border p-4 last:mb-0">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold">
                        {{ $slot->slot_date }} · {{ $slot->start_time }}–{{ $slot->end_time }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $slot->room_or_meet_link }}</p>
                    @if($slot->supervisor)
                        <p class="mt-1 text-xs text-slate-500">Supervisor: {{ $slot->supervisor->name }}</p>
                    @endif
                    @if($slot->bookedTeam)
                        <p class="mt-1 text-sm font-medium text-indigo-700">
                            Booked by {{ $slot->bookedTeam->group_name }}
                            @if($slot->agenda) · Agenda: {{ $slot->agenda }} @endif
                        </p>
                    @endif
                </div>
                <x-status-badge :status="$slot->status" :nextAction="$nextAction" />
            </div>

            @if($slot->status === 'available' && auth()->user()->role === 'student')
                <form method="POST" action="{{ route('consultations.slots.book', $slot) }}"
                      class="mt-4 flex flex-col gap-2 border-t pt-3 sm:flex-row">
                    @csrf
                    <select name="booked_by_team_id" required class="rounded border px-2 py-2 text-sm">
                        @forelse($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->group_name }}</option>
                        @empty
                            <option value="">No teams available</option>
                        @endforelse
                    </select>
                    <input type="text" name="agenda" placeholder="Discussion agenda" required maxlength="255"
                           class="min-w-0 flex-1 rounded border px-3 py-2 text-sm">
                    <button type="submit" @disabled($teams->isEmpty())
                            class="rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Book slot
                    </button>
                </form>
            @endif

            @if($slot->status === 'booked')
                <form method="POST" action="{{ route('consultations.slots.cancel', $slot) }}" class="mt-3 flex justify-end border-t pt-3">
                    @csrf
                    <button type="submit" class="rounded border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                        Cancel booking and reopen slot
                    </button>
                </form>
            @endif
        </article>
    @empty
        <p class="rounded-lg border border-dashed p-6 text-center text-sm text-slate-500">No consultation slots match these filters.</p>
    @endforelse
</section>
@endsection
