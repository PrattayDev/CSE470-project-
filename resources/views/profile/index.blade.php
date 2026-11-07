@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Feature 1: Multi-Role Academic Profile & Skill Matrix Management</h2>
<p class="text-sm text-slate-500 mb-6">Manage authenticated student/faculty profiles, technical skill tags, CGPA (students), and supervision group quotas (faculty).</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold text-lg">Update My Profile</h3>
            <span class="uppercase text-xs font-bold px-2.5 py-1 rounded bg-indigo-100 text-indigo-800">{{ $user->role }}</span>
        </div>

        <!-- Profile Completeness Bar (F1 Enhancement) -->
        <div class="mb-4 p-3 rounded-lg bg-slate-50 border">
            <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                <span>Profile Completeness</span>
                <span class="text-indigo-700">{{ $user->profileCompleteness() }}%</span>
            </div>
            <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $user->profileCompleteness() }}%"></div>
            </div>
        </div>
        <form method="POST" action="{{ route('profiles.update') }}" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block font-medium mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border rounded px-3 py-2" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium mb-1">Section</label>
                    <input type="text" name="section" value="{{ old('section', $user->section) }}" class="w-full border rounded px-3 py-2">
                </div>
                @if($user->role === 'student')
                <div>
                    <label class="block font-medium mb-1">CGPA (0.00–4.00)</label>
                    <input type="number" step="0.01" min="0" max="4" name="cgpa" value="{{ old('cgpa', $user->cgpa) }}" class="w-full border rounded px-3 py-2">
                </div>
                @elseif(in_array($user->role, ['supervisor', 'coordinator']))
                <div>
                    <label class="block font-medium mb-1">Supervision Quota</label>
                    <input type="number" min="1" max="20" name="max_group_quota" value="{{ old('max_group_quota', $user->max_group_quota ?? 5) }}" class="w-full border rounded px-3 py-2" required>
                </div>
                @endif
            </div>
            <div>
                <label class="block font-medium mb-1">Technical Skills (Comma-Separated)</label>
                <input type="text" name="skills_csv" value="{{ old('skills_csv', is_array($user->skills) ? implode(', ', $user->skills) : '') }}" class="w-full border rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block font-medium mb-1">Research / Domain Interests</label>
                <textarea name="research_interests" rows="2" class="w-full border rounded px-3 py-2">{{ old('research_interests', $user->research_interests) }}</textarea>
            </div>
            <div>
                <label class="block font-medium mb-1">GitHub Username</label>
                <input type="text" name="github_username" value="{{ old('github_username', $user->github_username) }}" class="w-full border rounded px-3 py-2">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded font-semibold">Save Skill Matrix</button>
        </form>

        @if(!empty($supervisorMatches) && $supervisorMatches->isNotEmpty())
        <div class="mt-6 pt-4 border-t">
            <h4 class="font-bold text-sm text-slate-800 mb-2">Recommended Supervisors (Match Score)</h4>
            <div class="space-y-2 text-xs">
                @foreach($supervisorMatches as $match)
                    <div class="p-2.5 rounded border bg-slate-50 flex justify-between items-center">
                        <div>
                            <div class="font-semibold text-slate-800">{{ $match['supervisor']->name }}</div>
                            <div class="text-[11px] text-slate-500">Quota: {{ $match['remaining_slots'] }} free • Shared: {{ implode(', ', array_slice($match['shared_tokens'], 0, 4)) ?: 'General MVC' }}</div>
                        </div>
                        <span class="px-2 py-1 rounded font-bold {{ $match['score'] >= 40 ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">{{ $match['score'] }}% Match</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-3 mb-4">
            <h3 class="font-bold text-lg">Academic Skill Directory</h3>
            <form method="GET" action="{{ route('profiles.index') }}" class="flex flex-wrap gap-2 text-xs">
                <input type="text" name="skill" value="{{ $skillFilter }}" placeholder="Skill (e.g. Laravel)" class="border rounded px-2.5 py-1.5">
                <select name="role" class="border rounded px-2.5 py-1.5">
                    <option value="">All Roles</option>
                    <option value="student" @selected(($roleFilter ?? '') === 'student')>Student</option>
                    <option value="supervisor" @selected(($roleFilter ?? '') === 'supervisor')>Supervisor</option>
                    <option value="lab_admin" @selected(($roleFilter ?? '') === 'lab_admin')>Lab Admin</option>
                    <option value="coordinator" @selected(($roleFilter ?? '') === 'coordinator')>Coordinator</option>
                </select>
                <input type="text" name="section" value="{{ $sectionFilter ?? '' }}" placeholder="Sec (e.g. 09)" class="border rounded px-2.5 py-1.5 w-24">
                <button type="submit" class="bg-slate-800 text-white px-3 py-1.5 rounded font-semibold">Filter</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="p-3">ID & Name</th>
                        <th class="p-3">Role / Academic Metric</th>
                        <th class="p-3">Skill Matrix</th>
                        <th class="p-3">Research Domain</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($directory as $person)
                    <tr>
                        <td class="p-3">
                            <div class="font-semibold">{{ $person->name }}</div>
                            <div class="text-xs text-slate-500">{{ $person->student_or_faculty_id }} @if($person->github_username) • <span class="font-mono">@<span>{{ $person->github_username }}</span></span> @endif</div>
                        </td>
                        <td class="p-3 text-xs">
                            <span class="uppercase font-bold">{{ $person->role }}</span> (Sec: {{ $person->section ?? 'N/A' }})
                            @if($person->role === 'student' && $person->cgpa !== null)
                                <div class="text-emerald-700 font-semibold mt-0.5">CGPA: {{ number_format((float)$person->cgpa, 2) }}</div>
                            @elseif(in_array($person->role, ['supervisor', 'coordinator']))
                                <div class="text-indigo-700 font-semibold mt-0.5">Quota: {{ $person->remainingSupervisorSlots() }} / {{ $person->max_group_quota }} free</div>
                            @endif
                        </td>
                        <td class="p-3">
                            @foreach($person->skills ?? [] as $sk)
                                <span class="inline-block bg-indigo-50 text-indigo-700 text-xs px-2 py-0.5 rounded mr-1 mb-1">{{ $sk }}</span>
                            @endforeach
                        </td>
                        <td class="p-3 text-xs text-slate-600">{{ $person->research_interests }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
