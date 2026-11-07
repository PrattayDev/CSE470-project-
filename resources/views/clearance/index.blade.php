@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Feature 19: Automated Certificate & Lab Clearance Sheet Generator</h2>
<p class="text-sm text-slate-500 mb-6">Automatically verifies 0 unreturned lab equipment, 0 unpaid fines, and an approved SRS document before issuing printable clearance certificates.</p>

<div class="bg-white p-6 rounded-xl shadow-sm border mb-6">
    <h3 class="font-bold mb-4">Team Clearance Eligibility Matrix</h3>
    <table class="w-full text-left text-sm border">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-3">Team</th>
                <th class="p-3">Unreturned Lab Items</th>
                <th class="p-3">Unpaid Fines</th>
                <th class="p-3">SRS Approval</th>
                <th class="p-3">Eligibility</th>
                <th class="p-3">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($teams as $t)
                @php $el = $eligibilityMap[$t->id]; @endphp
                <tr>
                    <td class="p-3 font-bold">{{ $t->group_name }}</td>
                    <td class="p-3">{{ $el['unreturned_equipment_count'] }} items</td>
                    <td class="p-3">{{ $el['outstanding_fine_bdt'] }} BDT</td>
                    <td class="p-3">
                        @if($el['srs_approved'])
                            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-xs font-bold">APPROVED</span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-xs font-bold">PENDING</span>
                        @endif
                    </td>
                    <td class="p-3">
                        @if($el['is_eligible'])
                            <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-800 text-xs font-bold">ELIGIBLE</span>
                        @else
                            <span class="px-2 py-1 rounded bg-rose-100 text-rose-800 text-xs font-bold">BLOCKED</span>
                        @endif
                    </td>
                    <td class="p-3">
                        @if(in_array(auth()->user()->role ?? '', ['lab_admin', 'supervisor', 'coordinator'], true))
                        <form method="POST" action="{{ route('clearance.generate') }}">
                            @csrf
                            <input type="hidden" name="team_id" value="{{ $t->id }}">
                            <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded text-xs font-semibold">Generate Clearance</button>
                        </form>
                        @else
                            <span class="text-xs text-slate-500">Faculty / Lab Admin Only</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@foreach($certificates as $cert)
<div class="bg-emerald-50 border border-emerald-300 p-4 rounded-xl flex justify-between items-center">
    <div>
        <div class="font-bold text-emerald-900">Serial: {{ $cert->certificate_serial }} ({{ $cert->team->group_name ?? '' }})</div>
        <div class="text-xs text-emerald-700">Issued At: {{ $cert->issued_at }}</div>
    </div>
    <a href="{{ route('clearance.print', $cert) }}" target="_blank" class="bg-emerald-700 text-white px-4 py-2 rounded text-xs font-bold">Open Printable Certificate</a>
</div>
@endforeach
@endsection
