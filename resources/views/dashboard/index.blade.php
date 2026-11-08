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
<div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white p-6 rounded-xl shadow-sm mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-white/15">
        <div>
            <span class="text-xs uppercase tracking-wider text-indigo-300 font-bold">{{ auth()->user()->role }} Workspace</span>
            <h2 class="text-lg font-bold mt-0.5">Welcome back, {{ auth()->user()->name }} ({{ auth()->user()->student_or_faculty_id }})</h2>
        </div>
        <span class="inline-flex items-center self-start sm:self-auto px-3 py-1 rounded-full bg-white/10 text-indigo-200 text-xs font-semibold">
            <i class="fa-solid fa-layer-group mr-1.5"></i> Section {{ auth()->user()->section ?? '09' }}
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4 text-xs">
        @if(auth()->user()->role === 'student')
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">My Team</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['my_team']->group_name ?? 'No Team Yet' }} <span class="text-indigo-300">({{ $roleSummary['my_team']->status ?? 'forming' }})</span></div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Latest Sprint</div>
                <div class="font-bold text-sm mt-1">
                    @if(!empty($roleSummary['active_sprint']))
                        Sprint {{ $roleSummary['active_sprint']->sprint_number }} ({{ $roleSummary['active_sprint']->attainment_percentage }}% Attainment)
                    @else
                        Not Started
                    @endif
                </div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Active Lab Loans</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['active_loans'] ?? 0 }} Active Request(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Unpaid Lab Fine</div>
                <div class="font-bold text-sm text-amber-300 mt-1">{{ number_format($roleSummary['outstanding_fine'] ?? 0, 2) }} BDT</div>
            </div>
        @elseif(auth()->user()->role === 'supervisor')
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Pending Proposals</div>
                <div class="font-bold text-sm text-amber-300 mt-1">{{ $roleSummary['pending_proposals'] ?? 0 }} Bid(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Docs Awaiting Review</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['pending_documents'] ?? 0 }} Version(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Sprints Awaiting Sign-off</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['submitted_sprints'] ?? 0 }} Sprint(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Upcoming Consultations</div>
                <div class="font-bold text-sm text-emerald-300 mt-1">{{ $roleSummary['today_consultations'] ?? 0 }} Booked</div>
            </div>
        @elseif(auth()->user()->role === 'lab_admin')
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Pending Requisitions</div>
                <div class="font-bold text-sm text-amber-300 mt-1">{{ $roleSummary['pending_requisitions'] ?? 0 }} Request(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Issued / Overdue Loans</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['active_loans'] ?? 0 }} Active</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Low-Stock Alerts</div>
                <div class="font-bold text-sm text-rose-300 mt-1">{{ $roleSummary['low_stock_items'] ?? 0 }} Asset(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Outstanding Fines</div>
                <div class="font-bold text-sm text-amber-300 mt-1">{{ number_format($roleSummary['unpaid_fines_bdt'] ?? 0, 2) }} BDT</div>
            </div>
        @elseif(auth()->user()->role === 'coordinator')
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Open Scope Disputes</div>
                <div class="font-bold text-sm text-amber-300 mt-1">{{ $roleSummary['pending_disputes'] ?? 0 }} Request(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Pending Topic Bids</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['pending_proposals'] ?? 0 }} Proposal(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Locked 4-Member Teams</div>
                <div class="font-bold text-sm text-emerald-300 mt-1">{{ $roleSummary['locked_teams'] ?? 0 }} Team(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Cohort Analytics</div>
                <a href="{{ route('analytics.index') }}" class="font-bold text-sm text-indigo-300 underline inline-block mt-1">Open F20 Dashboard →</a>
            </div>
        @endif
    </div>

    <div class="mt-4 pt-4 border-t border-white/15 flex flex-wrap items-center gap-2 text-xs">
        <span class="text-indigo-200 font-semibold mr-1"><i class="fa-solid fa-bolt mr-1"></i>Your Next Recommended Actions:</span>
        @if(auth()->user()->role === 'student')
            <a href="{{ route('sprints.index') }}" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-semibold">1. Update Sprint Tasks & Friday Report (F5/F6)</a>
            <a href="{{ route('proposals.index') }}" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">2. Check Topic Similarity & Proposal (F3/F4)</a>
            <a href="{{ route('documents.index') }}" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">3. Commit SRS / UML Version (F9)</a>
            <a href="{{ route('inventory.index') }}" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">4. Request Lab Equipment (F13)</a>
        @elseif(auth()->user()->role === 'supervisor')
            <a href="{{ route('proposals.index') }}?status=pending" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold">1. Review Pending Proposals ({{ $roleSummary['pending_proposals'] ?? 0 }})</a>
            <a href="{{ route('sprints.index') }}" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">2. Sign Off & Lock Sprints ({{ $roleSummary['submitted_sprints'] ?? 0 }})</a>
            <a href="{{ route('documents.index') }}" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">3. Approve SRS Versions ({{ $roleSummary['pending_documents'] ?? 0 }})</a>
            <a href="{{ route('evaluations.index') }}" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">4. Enter 15-Mark Viva Grades (F10)</a>
        @elseif(auth()->user()->role === 'lab_admin')
            <a href="{{ route('inventory.index') }}?req_status=requested" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold">1. Issue Pending Requisitions ({{ $roleSummary['pending_requisitions'] ?? 0 }})</a>
            <a href="{{ route('inventory.index') }}?req_status=issued" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">2. Process Hardware Returns & Fines ({{ $roleSummary['active_loans'] ?? 0 }})</a>
            <a href="{{ route('clearance.index') }}" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">3. Verify & Issue Clearance Sheet (F19)</a>
        @elseif(auth()->user()->role === 'coordinator')
            <a href="{{ route('disputes.index') }}" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold">1. Resolve Scope Disputes ({{ $roleSummary['pending_disputes'] ?? 0 }})</a>
            <a href="{{ route('evaluations.index') }}" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">2. Finalize & Lock Rubric Grades (F10)</a>
            <a href="{{ route('defense.index') }}" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">3. Schedule Viva Defense Panels (F14)</a>
            <a href="{{ route('analytics.index') }}" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-semibold">4. Open Cohort Analytics (F20)</a>
        @endif
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
                @php
                    $nextSprintStep = match($sprint->status) {
                        'planned' => 'Start Sprint Tasks',
                        'in_progress' => 'Submit Friday Report',
                        'submitted' => 'Awaiting Supervisor Sign-Off',
                        default => null,
                    };
                @endphp
                <div class="border rounded-lg p-4">
                    <div class="flex flex-wrap justify-between items-center gap-2">
                        <a href="{{ route('sprints.index') }}" class="font-semibold text-indigo-700 hover:underline">Sprint {{ $sprint->sprint_number }} ({{ $sprint->team->group_name ?? 'Team' }})</a>
                        <x-status-badge :status="$sprint->status" :nextAction="$nextSprintStep" />
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
