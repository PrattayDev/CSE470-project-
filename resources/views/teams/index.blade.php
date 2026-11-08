@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Feature 2: Smart Team Formation & Teammate Finder</h2>
<p class="text-sm text-slate-500 mb-6">Enforces strict 4-student group limit, skill-based teammate discovery, and module assignment.</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <h3 class="font-bold text-lg mb-4">Initialize 4-Member Group</h3>
        <form method="POST" action="{{ route('teams.store') }}" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block font-medium mb-1">Group Name</label>
                <input type="text" name="group_name" placeholder="Group 3 - Section 9" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Section</label>
                <input type="text" name="section" value="09" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Preferred MVC Stack</label>
                <input type="text" name="preferred_stack" value="Laravel 10 MVC + Blade + MySQL" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Domain Interest</label>
                <input type="text" name="domain_interest" placeholder="University Workflow Automation" required class="w-full border rounded px-3 py-2">
            </div>
            <button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded font-semibold">Create Group</button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border">
        <h3 class="font-bold text-lg mb-4">Active CSE 470 Project Groups</h3>
        <div class="space-y-4">
            @foreach($teams as $team)
            <div class="border rounded-lg p-4">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <span class="font-bold text-lg text-slate-800">{{ $team->group_name }}</span>
                        <span class="text-xs ml-2 px-2 py-0.5 rounded bg-indigo-100 text-indigo-800">{{ $team->preferred_stack }}</span>
                    </div>
                    <span class="text-xs font-bold uppercase px-2.5 py-1 rounded bg-emerald-100 text-emerald-800">{{ $team->status }} ({{ $team->members->count() }}/4 Members)</span>
                </div>
                <p class="text-xs text-slate-500 mb-3">Supervisor: {{ $team->supervisor->name ?? 'Unassigned' }} | Domain: {{ $team->domain_interest }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                    @foreach($team->members as $m)
                    <div class="p-2.5 bg-slate-50 rounded border flex justify-between items-center">
                        <div>
                            <div class="font-semibold">{{ $m->student->name ?? 'Student' }} ({{ $m->student->student_or_faculty_id ?? '' }})</div>
                            <div class="text-slate-500">{{ $m->assigned_module }}</div>
                        </div>
                        @if(auth()->check() && ((int) $team->leader_id === (int) auth()->id() || (int) $m->student_id === (int) auth()->id() || auth()->user()->role === 'coordinator') && (int) $m->student_id !== (int) $team->leader_id)
                        <form method="POST" action="{{ route('teams.members.remove', [$team, $m]) }}" onsubmit="return confirm('Remove this member from the group?');">
                            @csrf
                            <button type="submit" class="text-rose-600 hover:text-rose-800 text-[11px] font-bold px-2 py-0.5 border border-rose-200 rounded bg-white">
                                {{ (int) $m->student_id === (int) auth()->id() ? 'Leave' : 'Remove' }}
                            </button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                    @for($slotIdx = $team->members->count() + 1; $slotIdx <= 4; $slotIdx++)
                    <div class="p-2.5 bg-slate-50/50 rounded border border-dashed border-slate-300 text-slate-400 flex items-center justify-between">
                        <span>Open Member Slot #{{ $slotIdx }}</span>
                        <span class="text-[10px] uppercase font-semibold">Available</span>
                    </div>
                    @endfor
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-lg">Smart Teammate Finder & Invite</h3>
            <form method="GET" action="{{ route('teams.index') }}" class="flex space-x-2 text-xs">
                <input type="text" name="skill" value="{{ $searchSkill }}" placeholder="Skill (e.g. Laravel)" class="border rounded px-2.5 py-1">
                <input type="text" name="section" value="{{ $searchSection }}" placeholder="Sec (e.g. 09)" class="border rounded px-2.5 py-1 w-20">
                <button type="submit" class="bg-slate-800 text-white px-3 py-1 rounded">Search</button>
            </form>
        </div>
        <div class="space-y-3 text-sm max-h-80 overflow-y-auto">
            @foreach($availableStudents as $stu)
            <div class="p-3 border rounded-lg flex justify-between items-center">
                <div>
                    <div class="font-semibold">{{ $stu->name }} <span class="text-xs text-slate-500">({{ $stu->student_or_faculty_id }} • Sec {{ $stu->section ?? '09' }})</span></div>
                    <div class="mt-1">
                        @foreach($stu->skills ?? [] as $sk)
                            <span class="inline-block bg-emerald-50 text-emerald-700 text-[11px] px-2 py-0.5 rounded mr-1">{{ $sk }}</span>
                        @endforeach
                    </div>
                </div>
                @if(auth()->user()->role === 'student' && !$stu->has_group && $myTeams->isNotEmpty())
                <form method="POST" action="{{ route('teams.invite') }}" class="flex items-center space-x-1.5 text-xs">
                    @csrf
                    <input type="hidden" name="invited_student_id" value="{{ $stu->id }}">
                    <select name="team_id" class="border rounded px-2 py-1">
                        @foreach($myTeams as $t)
                            <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="message" value="Join our CSE 470 MVC Team!">
                    <button type="submit" class="bg-indigo-600 text-white px-2.5 py-1 rounded font-medium">Invite</button>
                </form>
                @elseif($stu->has_group)
                <span class="text-xs font-semibold text-slate-400">In a Group</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <h3 class="font-bold text-lg mb-4">Teammate Invitations & Auto-Lock Queue</h3>
        <div class="space-y-3 text-sm">
            @forelse($invitations as $inv)
            <div class="p-3 border rounded-lg">
                <div class="flex justify-between items-center">
                    <div>
                        <strong>{{ $inv->invitedStudent->name ?? 'Student' }}</strong> invited to <strong>{{ $inv->team->group_name ?? 'Group' }}</strong>
                        <p class="text-xs text-slate-500">{{ $inv->message }}</p>
                    </div>
                    <span class="uppercase text-xs font-bold px-2 py-0.5 rounded bg-slate-100">{{ $inv->status }}</span>
                </div>
                @if($inv->status === 'pending' && (int) $inv->invited_student_id === (int) auth()->id())
                <form method="POST" action="{{ route('teams.respond', $inv) }}" class="mt-2 pt-2 border-t flex space-x-2 text-xs">
                    @csrf
                    <input type="text" name="assigned_module" placeholder="Assigned 5-Feature Module..." value="Core MVC Feature Module" class="flex-1 border rounded px-2 py-1">
                    <button type="submit" name="decision" value="accepted" class="bg-emerald-600 text-white px-2.5 py-1 rounded">Accept</button>
                    <button type="submit" name="decision" value="declined" class="bg-rose-600 text-white px-2.5 py-1 rounded">Decline</button>
                </form>
                @endif
            </div>
            @empty
            <p class="text-xs text-slate-500">No teammate invitations sent yet. Use the Smart Teammate Finder to invite a student.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
