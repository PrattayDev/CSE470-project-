@extends('layouts.app')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">UniNexus — CSE 470 MVC System Dashboard</h1>
        <p class="text-sm text-slate-500">Group 3 (Section 09): Prattay Dev Burman (23101048), Rownak Jahan (23101159), Samara Shahjeen Huq (23301214), Md. Siddique Bishwas (22301762)</p>
    </div>
    <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1.5 rounded-full">Laravel 10 MVC + MySQL</span>
</div>

<!-- Role-Specific Personalized Workspace Banner -->
@auth
<div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white p-5 rounded-xl shadow-sm mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <span class="text-xs uppercase tracking-wider text-indigo-300 font-bold">{{ auth()->user()->role }} Workspace</span>
            <h2 class="text-lg font-bold mt-0.5">Welcome back, {{ auth()->user()->name }} ({{ auth()->user()->student_or_faculty_id }})</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            @if(auth()->user()->role === 'student')
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">My Team</div>
                    <div class="font-bold text-sm">{{ $roleSummary['my_team']->group_name ?? 'No Team Yet' }} ({{ $roleSummary['my_team']->status ?? 'forming' }})</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Latest Sprint</div>
                    <div class="font-bold text-sm">
                        @if(!empty($roleSummary['active_sprint']))
                            Sprint {{ $roleSummary['active_sprint']->sprint_number }} ({{ $roleSummary['active_sprint']->attainment_percentage }}%)
                        @else
                            Not Started
                        @endif
                    </div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Active Lab Loans</div>
                    <div class="font-bold text-sm">{{ $roleSummary['active_loans'] ?? 0 }} Request(s)</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Unpaid Lab Fine</div>
                    <div class="font-bold text-sm text-amber-300">{{ number_format($roleSummary['outstanding_fine'] ?? 0, 2) }} BDT</div>
                </div>
            @elseif(auth()->user()->role === 'supervisor')
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Pending Proposals</div>
                    <div class="font-bold text-sm text-amber-300">{{ $roleSummary['pending_proposals'] ?? 0 }} Bid(s)</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Docs Awaiting Review</div>
                    <div class="font-bold text-sm">{{ $roleSummary['pending_documents'] ?? 0 }} Version(s)</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Sprints Awaiting Sign-off</div>
                    <div class="font-bold text-sm">{{ $roleSummary['submitted_sprints'] ?? 0 }} Sprint(s)</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Upcoming Consultations</div>
                    <div class="font-bold text-sm text-emerald-300">{{ $roleSummary['today_consultations'] ?? 0 }} Booked</div>
                </div>
            @elseif(auth()->user()->role === 'lab_admin')
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Pending Requisitions</div>
                    <div class="font-bold text-sm text-amber-300">{{ $roleSummary['pending_requisitions'] ?? 0 }} Request(s)</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Issued / Overdue Loans</div>
                    <div class="font-bold text-sm">{{ $roleSummary['active_loans'] ?? 0 }} Active</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Low-Stock Alerts</div>
                    <div class="font-bold text-sm text-rose-300">{{ $roleSummary['low_stock_items'] ?? 0 }} Asset(s)</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Outstanding Fines</div>
                    <div class="font-bold text-sm text-amber-300">{{ number_format($roleSummary['unpaid_fines_bdt'] ?? 0, 2) }} BDT</div>
                </div>
            @elseif(auth()->user()->role === 'coordinator')
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Open Scope Disputes</div>
                    <div class="font-bold text-sm text-amber-300">{{ $roleSummary['pending_disputes'] ?? 0 }} Request(s)</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Pending Topic Bids</div>
                    <div class="font-bold text-sm">{{ $roleSummary['pending_proposals'] ?? 0 }} Proposal(s)</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Locked 4-Member Teams</div>
                    <div class="font-bold text-sm text-emerald-300">{{ $roleSummary['locked_teams'] ?? 0 }} Team(s)</div>
                </div>
                <div class="bg-white/10 px-3 py-2 rounded-lg">
                    <div class="text-slate-300">Cohort Analytics</div>
                    <a href="{{ route('analytics.index') }}" class="font-bold text-sm text-indigo-300 underline">Open F20 Dashboard →</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endauth

<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs text-slate-400 uppercase font-semibold">Registered Users</div>
        <div class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['users'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs text-slate-400 uppercase font-semibold">Active 4-Member Teams</div>
        <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['teams'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs text-slate-400 uppercase font-semibold">Topic Proposals</div>
        <div class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['proposals'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs text-slate-400 uppercase font-semibold">Tracked Sprints</div>
        <div class="text-2xl font-bold text-sky-600 mt-1">{{ $stats['sprints'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs text-slate-400 uppercase font-semibold">Available Lab Stock</div>
        <div class="text-2xl font-bold text-rose-600 mt-1">{{ $stats['equipments'] }} Units</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <h3 class="font-bold text-lg mb-4">Weekly Sprint Progress (Features 5 & 6)</h3>
        <div class="space-y-4">
            @foreach($recentSprints as $sprint)
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-indigo-700">Sprint {{ $sprint->sprint_number }} ({{ $sprint->team->group_name ?? 'Team' }})</span>
                        <span class="text-xs px-2 py-1 rounded bg-slate-100 uppercase font-bold">{{ $sprint->status }}</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">{{ $sprint->sprint_goal }}</p>
                    <div class="w-full bg-slate-200 h-2.5 rounded-full mt-3 overflow-hidden">
                        <div class="bg-emerald-500 h-2.5" style="width: {{ $sprint->attainment_percentage }}%"></div>
                    </div>
                    <div class="text-xs text-slate-500 mt-1">Attainment: {{ $sprint->attainment_percentage }}% ({{ $sprint->start_date }} to {{ $sprint->end_date }})</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <h3 class="font-bold text-lg mb-4">20 Substantive Features Directory</h3>
        <div class="grid grid-cols-2 gap-2 text-xs">
            <a href="{{ route('profiles.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#1</strong> Skill Matrix Profile</a>
            <a href="{{ route('teams.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#2</strong> Smart Team Finder</a>
            <a href="{{ route('proposals.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#3</strong> Proposal Bidding</a>
            <a href="{{ route('proposals.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#4</strong> Similarity Checker</a>
            <a href="{{ route('sprints.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#5</strong> Sprint Planner</a>
            <a href="{{ route('sprints.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#6</strong> Attainment Review</a>
            <a href="{{ route('consultations.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#7</strong> Slot Booking</a>
            <a href="{{ route('consultations.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#8</strong> Meeting Minutes</a>
            <a href="{{ route('documents.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#9</strong> Document Versioning</a>
            <a href="{{ route('evaluations.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#10</strong> Rubric Viva Grading</a>
            <a href="{{ route('evaluations.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#11</strong> Peer Weightage Calc</a>
            <a href="{{ route('inventory.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#12</strong> Hardware Inventory</a>
            <a href="{{ route('inventory.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#13</strong> Requisition & Penalty</a>
            <a href="{{ route('defense.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#14</strong> Defense Scheduler</a>
            <a href="{{ route('notifications.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#15</strong> Deadline Reminders</a>
            <a href="{{ route('archive.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#16</strong> Project Showcase</a>
            <a href="{{ route('compliance.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#17</strong> AI-Use Compliance</a>
            <a href="{{ route('disputes.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#18</strong> Scope & Disputes</a>
            <a href="{{ route('clearance.index') }}" class="p-2.5 border rounded hover:bg-slate-50"><strong>#19</strong> Clearance Generator</a>
            <a href="{{ route('analytics.index') }}" class="p-2.5 border rounded hover:bg-slate-50 flex items-center justify-between">
                <span><strong>#20</strong> Cohort Analytics</span>
                @if(auth()->user()?->role !== 'coordinator')
                    <span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold">Coordinator</span>
                @endif
            </a>
        </div>
    </div>
</div>
@endsection
