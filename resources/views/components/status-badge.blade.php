@props(['status' => 'pending', 'nextAction' => null])
@php
    $normalized = strtolower(trim((string) $status));
    $styles = match ($normalized) {
        'available', 'booked'
            => ['bg' => 'bg-indigo-100 text-indigo-800 border-indigo-200', 'icon' => 'fa-calendar-check'],
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
