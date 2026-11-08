@extends('layouts.app')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">UniNexus — CSE 470 MVC System Dashboard (Sprint 1)</h1>
        <p class="text-sm text-slate-500">Group 3 (Section 09): Prattay Dev Burman (23101048) & Rownak Jahan (23101159) — Sprint 1 Deliverables (F1, F2, F9)</p>
    </div>
    <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1.5 rounded-full">Laravel 10 MVC + MySQL</span>
</div>

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
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">My Team (F2)</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['my_team']->group_name ?? 'No Team Yet' }} <span class="text-indigo-300">({{ $roleSummary['my_team']->status ?? 'forming' }})</span></div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Profile Completeness (F1)</div>
                <div class="font-bold text-sm text-emerald-300 mt-1">{{ $roleSummary['profile_completeness'] ?? 0 }}% Complete</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Pending Team Invites (F2)</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['pending_invites'] ?? 0 }} Invitation(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Team Document Versions (F9)</div>
                <div class="font-bold text-sm text-sky-300 mt-1">{{ $roleSummary['team_documents'] ?? 0 }} Version(s)</div>
            </div>
        @elseif(auth()->user()->role === 'supervisor')
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Supervised Teams (F2)</div>
                <div class="font-bold text-sm text-emerald-300 mt-1">{{ $roleSummary['supervised_teams'] ?? 0 }} Group(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Remaining Quota (F1)</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['remaining_slots'] ?? 0 }} Slot(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Docs Awaiting Review (F9)</div>
                <div class="font-bold text-sm text-amber-300 mt-1">{{ $roleSummary['pending_documents'] ?? 0 }} Version(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Approved Docs (F9)</div>
                <div class="font-bold text-sm text-sky-300 mt-1">{{ $roleSummary['approved_documents'] ?? 0 }} Version(s)</div>
            </div>
        @else
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Locked/Supervised Teams</div>
                <div class="font-bold text-sm text-emerald-300 mt-1">{{ $roleSummary['locked_teams'] ?? 0 }} Team(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Forming Teams</div>
                <div class="font-bold text-sm mt-1">{{ $roleSummary['forming_teams'] ?? 0 }} Team(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Pending Docs</div>
                <div class="font-bold text-sm text-amber-300 mt-1">{{ $roleSummary['pending_documents'] ?? 0 }} Version(s)</div>
            </div>
            <div class="bg-white/10 p-3.5 rounded-lg border border-white/10">
                <div class="text-slate-300 uppercase tracking-wider text-[11px] font-semibold">Registered Students</div>
                <div class="font-bold text-sm text-sky-300 mt-1">{{ $roleSummary['total_students'] ?? 0 }} Student(s)</div>
            </div>
        @endif
    </div>
</div>
@endauth

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs text-slate-400 uppercase font-semibold">Registered Users (F1)</div>
        <div class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['users'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs text-slate-400 uppercase font-semibold">Active 4-Member Teams (F2)</div>
        <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['teams'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs text-slate-400 uppercase font-semibold">Pending Team Invites (F2)</div>
        <div class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['invitations'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="text-xs text-slate-400 uppercase font-semibold">Document Versions (F9)</div>
        <div class="text-2xl font-bold text-sky-600 mt-1">{{ $stats['documents'] }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <h3 class="font-bold text-lg mb-4">Sprint 1 Implemented Features</h3>
        <div class="space-y-3 text-sm">
            @if(\Illuminate\Support\Facades\Route::has('profiles.index'))
            <a href="{{ route('profiles.index') }}" class="block p-4 border rounded-lg hover:bg-slate-50">
                <div class="font-bold text-emerald-700">Feature 1: Multi-Role Academic Profile & Skill Matrix</div>
                <p class="text-xs text-slate-500 mt-1">Assigned to: Prattay Dev Burman (23101048) • Exact skill tag filter, profile completeness & supervisor match score.</p>
            </a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('teams.index'))
            <a href="{{ route('teams.index') }}" class="block p-4 border rounded-lg hover:bg-slate-50">
                <div class="font-bold text-emerald-700">Feature 2: Smart Team Formation & Teammate Finder</div>
                <p class="text-xs text-slate-500 mt-1">Assigned to: Prattay Dev Burman (23101048) • 4-student group initialization, skill-based invitations & auto-lock.</p>
            </a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('documents.index'))
            <a href="{{ route('documents.index') }}" class="block p-4 border rounded-lg hover:bg-slate-50">
                <div class="font-bold text-sky-700">Feature 9: Document Version Control & SRS Submission Portal</div>
                <p class="text-xs text-slate-500 mt-1">Assigned to: Rownak Jahan (23101159) • Sequential semantic versioning (v1.0 → v1.1), private storage & faculty review.</p>
            </a>
            @endif
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <h3 class="font-bold text-lg mb-4">Recent Document Submissions (Feature 9)</h3>
        <div class="space-y-3 text-sm">
            @forelse($recentDocuments as $doc)
                <div class="p-3.5 border rounded-lg flex items-center justify-between">
                    <div>
                        <div class="font-semibold">{{ $doc->document_type }} ({{ $doc->version_tag }})</div>
                        <div class="text-xs text-slate-500">{{ $doc->team->group_name ?? 'Team' }} • By {{ $doc->submitter->name ?? 'Student' }}</div>
                    </div>
                    <x-status-badge :status="$doc->faculty_review_status" />
                </div>
            @empty
                <p class="text-xs text-slate-500">No document versions submitted yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
