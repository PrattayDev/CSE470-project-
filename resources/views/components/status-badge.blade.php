@props(['status' => 'pending', 'nextAction' => null])
@php
    $normalized = strtolower(trim((string) $status));
    $styles = match ($normalized) {
        'approved', 'completed', 'resolved', 'returned', 'reviewed', 'verified', 'locked', 'supervised', 'cleared', 'excellent'
            => ['bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'icon' => 'fa-circle-check'],
        'pending', 'in_progress', 'requested', 'under_review', 'submitted', 'forming', 'good', 'carried_over', 'rescheduled'
            => ['bg' => 'bg-amber-100 text-amber-800 border-amber-200', 'icon' => 'fa-clock'],
        'available', 'booked', 'scheduled', 'planned', 'todo'
            => ['bg' => 'bg-indigo-100 text-indigo-800 border-indigo-200', 'icon' => 'fa-calendar-check'],
        'rejected', 'declined', 'overdue', 'needs_update', 'revision_required', 'damaged', 'maintenance', 'cancelled'
            => ['bg' => 'bg-rose-100 text-rose-800 border-rose-200', 'icon' => 'fa-triangle-exclamation'],
        default
            => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fa-circle-info'],
    };
    $label = str_replace('_', ' ', strtoupper($normalized));
@endphp
<span class="inline-flex items-center gap-1.5 flex-wrap">
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wide border {{ $styles['bg'] }}">
        <i class="fa-solid {{ $styles['icon'] }} mr-1 text-[10px]" aria-hidden="true"></i>{{ $label }}
    </span>
    @if($nextAction)
        <span class="text-[11px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-full">
            Next: {{ $nextAction }}
        </span>
    @endif
</span>
