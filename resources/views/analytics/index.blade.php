@extends('layouts.app')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-3">
    <div>
        <h2 class="text-2xl font-bold">Feature 20: Coordinator Analytics & Cohort Performance Dashboard</h2>
        <p class="text-sm text-slate-500">Executive overview of cohort sprint velocity, rubric grade averages, and lab loan metrics.</p>
    </div>
    <form method="GET" action="{{ route('analytics.index') }}" class="flex items-center space-x-2 text-xs">
        <label class="font-semibold text-slate-600">Section Filter:</label>
        <select name="section" class="border rounded px-2.5 py-1.5 bg-white">
            <option value="">All Sections</option>
            @foreach($sections ?? [] as $sec)
                <option value="{{ $sec }}" @selected(($sectionFilter ?? '') === $sec)>Section {{ $sec }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-slate-800 text-white px-3 py-1.5 rounded font-semibold">Apply</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <div class="text-xs text-slate-400 uppercase font-bold">Active Sprint Attainment</div>
        <div class="text-3xl font-extrabold text-emerald-600 mt-2">{{ $metrics['avg_sprint_attainment'] }}%</div>
        <p class="text-xs text-slate-500 mt-1">Excludes unstarted planned sprints</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <div class="text-xs text-slate-400 uppercase font-bold">Sprint Velocity</div>
        <div class="text-3xl font-extrabold text-teal-600 mt-2">{{ $metrics['avg_sprint_velocity'] }}</div>
        <p class="text-xs text-slate-500 mt-1">Completed features / active sprint</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <div class="text-xs text-slate-400 uppercase font-bold">Average Rubric Score (Out of 15)</div>
        <div class="text-3xl font-extrabold text-indigo-600 mt-2">{{ $metrics['avg_rubric_score'] ?? $metrics['avg_viva_total'] }} / 15.00</div>
        <p class="text-xs text-slate-500 mt-1">SRS (2) + MVC (3) + Viva (10)</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <div class="text-xs text-slate-400 uppercase font-bold">Approved Proposals</div>
        <div class="text-3xl font-extrabold text-sky-600 mt-2">{{ $metrics['approved_proposals'] }}</div>
        <p class="text-xs text-slate-500 mt-1">Across {{ $metrics['total_teams'] }} teams ({{ $metrics['total_students'] }} students)</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <div class="text-xs text-slate-400 uppercase font-bold">Active Lab Hardware Loans</div>
        <div class="text-3xl font-extrabold text-rose-600 mt-2">{{ $metrics['active_lab_loans'] }}</div>
        <p class="text-xs text-slate-500 mt-1">Tracked via PenaltyCalculationService</p>
    </div>
</div>

<!-- Chart.js Visual Analytics Row -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <h3 class="font-bold text-sm uppercase tracking-wider text-slate-500 mb-3">Sprint Attainment Trend (%)</h3>
        <canvas id="sprintAttainmentChart" height="140"></canvas>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <h3 class="font-bold text-sm uppercase tracking-wider text-slate-500 mb-3">Lab Hardware Requisition Breakdown</h3>
        <canvas id="loanStatusChart" height="140"></canvas>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sprintCtx = document.getElementById('sprintAttainmentChart');
        if (sprintCtx && typeof Chart !== 'undefined') {
            new Chart(sprintCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($sprints->map(fn($s) => 'Sprint ' . $s->sprint_number . ' (' . ($s->team->group_name ?? 'Team') . ')')->values()) !!},
                    datasets: [{
                        label: 'Attainment %',
                        data: {!! json_encode($sprints->pluck('attainment_percentage')->values()) !!},
                        backgroundColor: '#4f46e5',
                        borderRadius: 6
                    }]
                },
                options: { scales: { y: { beginAtZero: true, max: 100 } } }
            });
        }
        const loanCtx = document.getElementById('loanStatusChart');
        if (loanCtx && typeof Chart !== 'undefined') {
            new Chart(loanCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Requested', 'Issued', 'Returned', 'Overdue'],
                    datasets: [{
                        data: [
                            {{ $loanStatusCounts['requested'] ?? 0 }},
                            {{ $loanStatusCounts['issued'] ?? 0 }},
                            {{ $loanStatusCounts['returned'] ?? 0 }},
                            {{ $loanStatusCounts['overdue'] ?? 0 }}
                        ],
                        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444']
                    }]
                }
            });
        }
    });
</script>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <h3 class="font-bold text-lg mb-4">Sprint Velocity Breakdown</h3>
        <div class="space-y-3 text-sm">
            @foreach($sprints as $sp)
                <div class="p-3 border rounded-lg flex justify-between items-center">
                    <div>
                        <div class="font-bold">Sprint {{ $sp->sprint_number }} ({{ $sp->team->group_name ?? 'Team' }})</div>
                        <div class="text-xs text-slate-500">{{ $sp->sprint_goal }}</div>
                    </div>
                    <span class="font-bold text-emerald-700">{{ $sp->attainment_percentage }}%</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <h3 class="font-bold text-lg mb-4">Proposal Similarity & Approval Audit</h3>
        <div class="space-y-3 text-sm">
            @foreach($proposals as $pr)
                <div class="p-3 border rounded-lg flex justify-between items-center">
                    <div>
                        <div class="font-bold">{{ $pr->title }}</div>
                        <div class="text-xs text-slate-500">Team: {{ $pr->team->group_name ?? 'Team' }} • Similarity: {{ $pr->similarity_score }}%</div>
                    </div>
                    <span class="uppercase text-xs font-bold px-2 py-1 rounded bg-indigo-50 text-indigo-700">{{ $pr->status }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
