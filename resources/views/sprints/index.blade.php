@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Features 5 & 6: Weekly Sprint Planning & Attainment Review</h2>
<p class="text-sm text-slate-500 mb-6">Saturday Sprint Start planning (Sprints 0–4), member task breakdown with Git commit tracking, inline task status updates, and Friday Sprint Review submission.</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold text-base mb-3">Initialize Weekly Sprint (Saturday)</h3>
        <form method="POST" action="{{ route('sprints.store') }}" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block font-medium mb-1">Select Team</label>
                <select name="team_id" class="w-full border rounded px-3 py-1.5" required>
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}">{{ $t->group_name }} (Sec {{ $t->section }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-medium mb-1">Sprint Number (0 to 4)</label>
                <input type="number" name="sprint_number" value="2" min="0" max="4" required class="w-full border rounded px-3 py-1.5">
            </div>
            <div>
                <label class="block font-medium mb-1">Specific & Measurable Sprint Goal</label>
                <input type="text" name="sprint_goal" placeholder="Implement Features 6-10 with MVC validation" required class="w-full border rounded px-3 py-1.5">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-medium mb-1">Start (Saturday)</label>
                    <input type="date" name="start_date" value="2026-11-09" required class="w-full border rounded px-2 py-1.5">
                </div>
                <div>
                    <label class="block font-medium mb-1">End (Friday)</label>
                    <input type="date" name="end_date" value="2026-11-25" required class="w-full border rounded px-2 py-1.5">
                </div>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded font-semibold">Create Sprint Plan (0–4)</button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold text-base mb-3">Assign Feature Task & Link Git Commit</h3>
        <form method="POST" action="{{ route('sprints.tasks.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            @csrf
            <div>
                <label class="block font-medium mb-1">Target Sprint</label>
                <select name="sprint_id" class="w-full border rounded px-3 py-2 bg-white">
                    @foreach($sprints as $sp)
                        <option value="{{ $sp->id }}">Sprint {{ $sp->sprint_number }} ({{ $sp->sprint_goal }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-medium mb-1">Assign To Team Member</label>
                <select name="assigned_to" class="w-full border rounded px-3 py-2 bg-white">
                    @foreach($students as $stu)
                        <option value="{{ $stu->id }}">{{ $stu->name }} ({{ $stu->student_or_faculty_id }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-medium mb-1">Feature # (1-20)</label>
                <input type="number" name="feature_number" value="6" min="1" max="20" class="w-full border rounded px-3 py-2">
                <x-input-error field="feature_number" />
            </div>
            <div>
                <label class="block font-medium mb-1">Feature Title</label>
                <input type="text" name="feature_title" placeholder="Sprint Attainment Review" required class="w-full border rounded px-3 py-2">
                <x-input-error field="feature_title" />
            </div>
            <div>
                <label class="block font-medium mb-1">Git Commit Hash (7–40 Hex Chars)</label>
                <input type="text" name="git_commit_hash" placeholder="f8d9a2c" class="w-full border rounded px-3 py-2">
                <x-input-error field="git_commit_hash" />
            </div>
            <div>
                <label class="block font-medium mb-1">Task Status</label>
                <select name="status" class="w-full border rounded px-3 py-2 bg-white">
                    <option value="completed">Completed</option>
                    <option value="in_progress">In Progress</option>
                    <option value="todo">To Do</option>
                    <option value="carried_over">Carried Over</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block font-medium mb-1">Task Implementation Description</label>
                <input type="text" name="task_description" placeholder="Detailed MVC implementation description..." required class="w-full border rounded px-3 py-2">
                <x-input-error field="task_description" />
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded font-semibold">Add Task & Recalculate Attainment</button>
            </div>
        </form>
    </div>
</div>

<div class="space-y-4">
    @foreach($sprints as $sp)
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <div class="flex justify-between items-center mb-3">
            <div>
                <span class="font-bold text-lg">Sprint {{ $sp->sprint_number }}: {{ $sp->sprint_goal }}</span>
                <span class="text-xs text-slate-500 ml-2">({{ $sp->start_date }} → {{ $sp->end_date }})</span>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Attainment: {{ $sp->attainment_percentage }}%</span>
        </div>
        <table class="w-full text-left text-xs border mb-4">
            <thead class="bg-slate-100">
                <tr>
                    <th class="p-2">Feature #</th>
                    <th class="p-2">Feature & Task</th>
                    <th class="p-2">Member</th>
                    <th class="p-2">Git Commit</th>
                    <th class="p-2">Update Task Status (Auto-Recalculates Attainment)</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($sp->tasks as $tk)
                <tr>
                    <td class="p-2 font-bold">#{{ $tk->feature_number }}</td>
                    <td class="p-2"><strong>{{ $tk->feature_title }}</strong> — {{ $tk->task_description }}</td>
                    <td class="p-2">{{ $tk->assignee->name ?? '' }}</td>
                    <td class="p-2 font-mono text-indigo-600">{{ $tk->git_commit_hash ?? 'pending' }}</td>
                    <td class="p-2">
                        @if((auth()->user()->role ?? '') !== 'student' || (int) $tk->assigned_to === (int) auth()->id())
                        <form method="POST" action="{{ route('sprints.tasks.status', $tk) }}" class="flex items-center space-x-1">
                            @csrf
                            <input type="text" name="git_commit_hash" value="{{ $tk->git_commit_hash }}" placeholder="Commit hash" class="border rounded px-1.5 py-0.5 text-xs w-24">
                            <select name="status" class="border rounded px-1.5 py-0.5 text-xs">
                                <option value="todo" @selected($tk->status === 'todo')>To Do</option>
                                <option value="in_progress" @selected($tk->status === 'in_progress')>In Progress</option>
                                <option value="completed" @selected($tk->status === 'completed')>Completed</option>
                                <option value="carried_over" @selected($tk->status === 'carried_over')>Carried Over</option>
                            </select>
                            <button type="submit" class="bg-slate-800 text-white px-2 py-0.5 rounded text-xs font-semibold">Update</button>
                        </form>
                        @else
                            <span class="uppercase font-bold text-slate-600">{{ $tk->status }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="bg-slate-50 p-4 rounded-lg border text-xs">
            <div class="flex justify-between items-center mb-2">
                <div class="font-bold text-slate-700">Feature 6: Friday Sprint Attainment & Progress Review Workflow (Status: <span class="uppercase text-indigo-700">{{ $sp->status }}</span>)</div>
                @if($sp->status === 'reviewed')
                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold uppercase text-[10px]">Locked by Supervisor</span>
                @endif
            </div>
            <form method="POST" action="{{ route('sprints.attainment', $sp) }}" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                @csrf
                <input type="text" name="attainment_summary" value="{{ $sp->attainment_summary }}" placeholder="Friday attainment summary..." required class="border rounded px-2.5 py-1.5">
                <input type="text" name="blockers_reported" value="{{ $sp->blockers_reported }}" placeholder="Blockers (if any)..." class="border rounded px-2.5 py-1.5">
                <input type="url" name="github_pr_or_commit_url" value="{{ $sp->github_pr_or_commit_url ?? 'https://github.com/cse470-group3/uninexus/pull/1' }}" placeholder="GitHub PR URL" required class="border rounded px-2.5 py-1.5">
                <input type="url" name="demo_video_url" value="{{ $sp->demo_video_url }}" placeholder="Optional Demo URL (https://...)" class="border rounded px-2.5 py-1.5">
                <input type="text" name="supervisor_remarks" value="{{ $sp->supervisor_remarks }}" placeholder="Supervisor review remarks..." class="border rounded px-2.5 py-1.5" @if((auth()->user()->role ?? '') === 'student') readonly @endif>
                <div class="flex space-x-2">
                    <select name="status" class="border rounded px-2 py-1.5 flex-1">
                        <option value="in_progress" @selected($sp->status === 'in_progress')>In Progress</option>
                        <option value="submitted" @selected($sp->status === 'submitted')>Submitted (Friday)</option>
                        @if(in_array(auth()->user()->role ?? '', ['supervisor', 'coordinator'], true))
                            <option value="reviewed" @selected($sp->status === 'reviewed')>Supervisor Reviewed</option>
                        @endif
                    </select>
                    <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded font-semibold">Save Report</button>
                </div>
            </form>

            @if(in_array(auth()->user()->role ?? '', ['supervisor', 'coordinator'], true))
            <form method="POST" action="{{ route('sprints.review', $sp) }}" class="mt-3 pt-3 border-t flex flex-wrap items-center gap-2">
                @csrf
                <span class="font-bold text-slate-700">Supervisor Sign-Off & Lock:</span>
                <input type="text" name="supervisor_remarks" value="{{ $sp->supervisor_remarks }}" placeholder="Enter official supervisor sprint feedback..." required class="flex-1 border rounded px-2.5 py-1 bg-white">
                <select name="status" class="border rounded px-2 py-1 bg-white">
                    <option value="reviewed" @selected($sp->status === 'reviewed')>Accept & Lock Sprint (Reviewed)</option>
                    <option value="in_progress" @selected($sp->status === 'in_progress')>Return for Follow-Up (In Progress)</option>
                </select>
                <button type="submit" class="bg-emerald-600 text-white px-3 py-1 rounded font-semibold">Submit Supervisor Decision</button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endsection
