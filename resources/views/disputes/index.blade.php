@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Feature 18: Group Dispute & Scope Change Request Module</h2>
<p class="text-sm text-slate-500 mb-6">Formal workflow for mid-semester feature adjustments or member contribution disputes resolved by the Course Coordinator.</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">File Scope Change / Dispute Request</h3>
        @if(auth()->user()->role === 'student')
        <form method="POST" action="{{ route('disputes.store') }}" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block text-xs font-medium mb-1">My Project Group</label>
                <select name="team_id" class="w-full border rounded px-3 py-1.5" required>
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                    @endforeach
                </select>
            </div>
            <select name="request_type" class="w-full border rounded px-3 py-1.5">
                <option value="feature_change">Feature Scope Adjustment</option>
                <option value="member_dispute">Member Contribution Issue</option>
                <option value="stack_adjustment">External Package Approval</option>
            </select>
            <input type="text" name="current_scope_detail" placeholder="Current Feature Description" required class="w-full border rounded px-3 py-1.5">
            <input type="text" name="proposed_change_detail" placeholder="Proposed Modification" required class="w-full border rounded px-3 py-1.5">
            <textarea name="reason_justification" rows="2" placeholder="Technical justification for the change..." required class="w-full border rounded px-3 py-1.5"></textarea>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded font-semibold">Submit Request</button>
        </form>
        @else
        <div class="p-3 bg-slate-50 border rounded text-xs text-slate-600">
            Student teams submit scope change or dispute tickets here. Only the Course Coordinator can record official resolutions on the right.
        </div>
        @endif
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Request Log & Coordinator Resolution</h3>
        @forelse($requests as $r)
            <div class="p-3 border rounded mb-3 text-sm">
                <div class="flex justify-between items-center">
                    <strong>{{ strtoupper($r->request_type) }} • {{ $r->team->group_name ?? 'Team' }}</strong>
                    <span class="uppercase text-xs font-bold px-2 py-0.5 rounded bg-slate-100">{{ $r->status }}</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Current: {{ $r->current_scope_detail }} → Proposed: {{ $r->proposed_change_detail }}</p>
                <p class="text-xs text-slate-600">Justification: {{ $r->reason_justification }}</p>
                @if($r->coordinator_decision_note)
                    <div class="mt-2 p-2 bg-indigo-50 text-indigo-800 rounded text-xs">
                        <strong>Coordinator Decision:</strong> {{ $r->coordinator_decision_note }}
                    </div>
                @endif
                @if(auth()->user()->role === 'coordinator')
                <form method="POST" action="{{ route('disputes.decide', $r) }}" class="mt-2 pt-2 border-t flex space-x-2 text-xs">
                    @csrf
                    <select name="status" class="border rounded px-2 py-1">
                        <option value="under_review" @selected($r->status === 'under_review')>Under Review</option>
                        <option value="approved" @selected($r->status === 'approved')>Approve & Sync Scope</option>
                        <option value="rejected" @selected($r->status === 'rejected')>Reject</option>
                    </select>
                    <input type="text" name="coordinator_decision_note" value="{{ $r->coordinator_decision_note }}" placeholder="Coordinator resolution note..." required class="flex-1 border rounded px-2 py-1">
                    <button type="submit" class="bg-slate-800 text-white px-3 py-1 rounded">Save Decision</button>
                </form>
                @endif
            </div>
        @empty
            <p class="text-xs text-slate-500">No scope change or dispute requests logged.</p>
        @endforelse
    </div>
</div>
@endsection
