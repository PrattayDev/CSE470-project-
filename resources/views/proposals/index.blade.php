@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Features 3 & 4: Topic Proposal Bidding & Duplicate Similarity Checker</h2>
<p class="text-sm text-slate-500 mb-6">Submit 20-feature MVC proposals to supervisors with automated Jaccard keyword similarity checking against past archives.</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <h3 class="font-bold text-lg mb-4">Submit Project Proposal Bid</h3>

        @if(session('similarity_preview'))
            @php $simPreview = session('similarity_preview'); @endphp
            <div class="mb-4 p-3.5 rounded-lg border {{ $simPreview['similarity_score'] >= 45 ? 'bg-rose-50 border-rose-300 text-rose-900' : ($simPreview['similarity_score'] >= 25 ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-emerald-50 border-emerald-300 text-emerald-900') }} text-xs">
                <div class="flex justify-between items-center font-bold">
                    <span>F4 Pre-Submission Similarity Check</span>
                    <span class="text-sm">{{ $simPreview['similarity_score'] }}% ({{ strtoupper($simPreview['risk_band'] ?? 'low') }})</span>
                </div>
                <div class="mt-1">Closest Match: <strong>{{ $simPreview['matched_title'] }}</strong></div>
                @if(!empty($simPreview['shared_words']))
                    <div class="mt-1">Overlapping Keywords: <span class="font-mono">{{ implode(', ', $simPreview['shared_words']) }}</span></div>
                @endif
                @if(!empty($simPreview['top_matches']))
                    <div class="mt-2 pt-2 border-t border-slate-200/70 space-y-1">
                        <div class="font-semibold">Top 3 Archive / Cohort Matches:</div>
                        @foreach($simPreview['top_matches'] as $tm)
                            <div class="flex justify-between">
                                <span class="truncate pr-2">• {{ $tm['title'] }} ({{ $tm['source'] }})</span>
                                <span class="font-bold">{{ $tm['score'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('proposals.store') }}" class="space-y-3 text-sm" id="proposalBidForm">
            @csrf
            <div>
                <label class="block font-medium mb-1">Select Group</label>
                <select name="team_id" class="w-full border rounded px-3 py-2">
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-medium mb-1">Target Supervisor (Quota Tracked)</label>
                <select name="supervisor_id" class="w-full border rounded px-3 py-2">
                    @foreach($supervisors as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }} (Remaining Slots: {{ $sup->remainingSupervisorSlots() }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-medium mb-1">Unique Project Title</label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="UniNexus — Smart University Thesis & Lab System" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Approved MVC Tech Stack</label>
                <input type="text" name="tech_stack" value="{{ old('tech_stack', 'Laravel 10 (PHP) MVC + Blade + MySQL') }}" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Problem Statement</label>
                <textarea name="problem_statement" rows="3" required class="w-full border rounded px-3 py-2">{{ old('problem_statement') }}</textarea>
            </div>
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="block font-medium">20 Substantive Features (One per line)</label>
                    <span id="featureLineCounter" class="text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">0 / 20 features</span>
                </div>
                <textarea name="features_multiline" id="featuresMultilineInput" rows="5" placeholder="1. Multi-Role Academic Profile&#10;2. Smart Team Finder..." required class="w-full border rounded px-3 py-2">{{ old('features_multiline') }}</textarea>
            </div>
            <div class="grid grid-cols-1 gap-2">
                <button type="submit" formaction="{{ route('proposals.check_similarity') }}" formnovalidate class="w-full bg-slate-800 hover:bg-slate-900 text-white py-2 rounded font-semibold text-xs">
                    <i class="fa-solid fa-magnifying-glass-chart mr-1"></i> F4: Check Similarity Before Submit
                </button>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded font-semibold">
                    Run Similarity Check & Submit Proposal
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const textarea = document.getElementById('featuresMultilineInput');
            const badge = document.getElementById('featureLineCounter');
            if (!textarea || !badge) return;
            const updateCount = () => {
                const lines = textarea.value.split(/\r?\n/).map(s => s.trim()).filter(Boolean).length;
                badge.textContent = lines + ' / 20 features';
                badge.className = 'text-xs font-bold px-2 py-0.5 rounded ' + (lines === 20 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800');
            };
            textarea.addEventListener('input', updateCount);
            updateCount();
        })();
    </script>

    <div class="lg:col-span-2 space-y-4">
        <!-- Search & Filter Bar for Proposals (Improvement #4) -->
        <div class="bg-white p-4 rounded-xl shadow-sm border">
            <form method="GET" action="{{ route('proposals.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs">
                <div>
                    <label for="propSearchQ" class="block font-semibold text-slate-600 mb-1">Keyword / Stack</label>
                    <input id="propSearchQ" type="text" name="q" value="{{ $searchQuery ?? '' }}" placeholder="Search title or stack..." class="w-full border rounded px-2.5 py-2">
                </div>
                <div>
                    <label for="propFilterStatus" class="block font-semibold text-slate-600 mb-1">Proposal Status</label>
                    <select id="propFilterStatus" name="status" class="w-full border rounded px-2.5 py-2 bg-white">
                        <option value="">All Statuses</option>
                        <option value="pending" @selected(($statusFilter ?? '') === 'pending')>Pending</option>
                        <option value="approved" @selected(($statusFilter ?? '') === 'approved')>Approved</option>
                        <option value="revision_required" @selected(($statusFilter ?? '') === 'revision_required')>Revision Required</option>
                        <option value="rejected" @selected(($statusFilter ?? '') === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div>
                    <label for="propFilterTeam" class="block font-semibold text-slate-600 mb-1">Project Group</label>
                    <select id="propFilterTeam" name="team_id" class="w-full border rounded px-2.5 py-2 bg-white">
                        <option value="">All Teams</option>
                        @foreach($allTeams ?? $teams as $t)
                            <option value="{{ $t->id }}" @selected((string)($teamFilter ?? '') === (string)$t->id)>{{ $t->group_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-1.5">
                    <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white py-2 px-3 rounded font-semibold">Filter</button>
                    <a href="{{ route('proposals.index') }}" class="py-2 px-2.5 border rounded text-slate-600 hover:bg-slate-100">Reset</a>
                </div>
            </form>
        </div>

        @forelse($proposals as $prop)
        @php
            $propNextStep = match($prop->status) {
                'pending' => 'Supervisor Review',
                'revision_required' => 'Revise & Resubmit',
                default => null,
            };
        @endphp
        <div class="bg-white p-6 rounded-xl shadow-sm border">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-3">
                <div>
                    <x-status-badge :status="$prop->status" :nextAction="$propNextStep" />
                    <h3 class="text-lg font-bold mt-1.5">{{ $prop->title }}</h3>
                    <p class="text-xs text-slate-500">Team: {{ $prop->team->group_name ?? '' }} • Supervisor: {{ $prop->supervisor->name ?? '' }} • Stack: {{ $prop->tech_stack }}</p>
                </div>
                <div class="sm:text-right">
                    <div class="text-xs uppercase text-slate-400 font-bold">Similarity Score (Feature 4)</div>
                    <div class="text-xl font-bold {{ $prop->similarity_score >= 45 ? 'text-rose-600' : ($prop->similarity_score >= 25 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $prop->similarity_score }}%</div>
                    <div class="text-[11px] text-slate-500">Closest Match: {{ $prop->matched_archive_title }}</div>
                </div>
            </div>
            <p class="text-sm text-slate-700 mt-3">{{ $prop->problem_statement }}</p>
            <div class="mt-3 p-3 bg-slate-50 rounded border text-xs">
                <strong>Proposed Features ({{ count($prop->proposed_features ?? []) }}):</strong>
                <div class="grid grid-cols-2 gap-1 mt-1">
                    @foreach($prop->proposed_features ?? [] as $f)
                        <div>• {{ $f }}</div>
                    @endforeach
                </div>
            </div>
            @if($prop->supervisor_feedback)
                <div class="mt-3 text-xs text-emerald-800 bg-emerald-50 p-2.5 rounded">
                    <strong>Supervisor Feedback:</strong> {{ $prop->supervisor_feedback }}
                </div>
            @endif
            @if(in_array(auth()->user()->role, ['supervisor', 'coordinator']))
            <form method="POST" action="{{ route('proposals.review', $prop) }}" class="mt-4 pt-3 border-t flex flex-col sm:flex-row gap-2 text-xs">
                @csrf
                <label class="sr-only" for="decision_{{ $prop->id }}">Decision</label>
                <select id="decision_{{ $prop->id }}" name="status" class="border rounded px-2.5 py-2 bg-white">
                    <option value="approved" @selected($prop->status === 'approved')>Approve Proposal</option>
                    <option value="revision_required" @selected($prop->status === 'revision_required')>Request Revision</option>
                    <option value="rejected" @selected($prop->status === 'rejected')>Reject</option>
                </select>
                <label class="sr-only" for="feedback_{{ $prop->id }}">Supervisor Feedback</label>
                <input id="feedback_{{ $prop->id }}" type="text" name="supervisor_feedback" value="{{ $prop->supervisor_feedback }}" placeholder="Add faculty review note..." required class="flex-1 border rounded px-2.5 py-2">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded font-semibold">Update Decision</button>
            </form>
            @endif
        </div>
        @empty
        <div class="bg-white p-6 rounded-xl shadow-sm border text-center text-sm text-slate-500">
            No proposals match your current filter criteria.
        </div>
        @endforelse
    </div>
</div>
@endsection
