@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Feature 16: Project Showcase & Digital Archive Repository</h2>
<p class="text-sm text-slate-500 mb-6">Search past CSE 470 MVC projects by keyword, semester, or stack (also powers Feature 4 Similarity Checking).</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Archive Completed Project</h3>
        @if(in_array(auth()->user()->role, ['supervisor', 'coordinator']))
        <form method="POST" action="{{ route('archive.store') }}" class="space-y-3 text-sm">
            @csrf
            <input type="text" name="project_title" placeholder="Project Title" required class="w-full border rounded px-3 py-1.5">
            <input type="text" name="semester" value="Fall 2026" required class="w-full border rounded px-3 py-1.5">
            <input type="text" name="team_members_summary" placeholder="Group 3 (4 Members)" required class="w-full border rounded px-3 py-1.5">
            <input type="text" name="tech_stack" value="Laravel MVC + MySQL" required class="w-full border rounded px-3 py-1.5">
            <input type="text" name="keywords" placeholder="university thesis sprint lab inventory" required class="w-full border rounded px-3 py-1.5">
            <input type="url" name="github_repo_url" placeholder="https://github.com/cse470-group3/uninexus" class="w-full border rounded px-3 py-1.5">
            <textarea name="abstract" rows="3" placeholder="Project abstract..." required class="w-full border rounded px-3 py-1.5"></textarea>
            <label class="flex items-center space-x-2 text-xs font-medium">
                <input type="checkbox" name="featured_award" value="1">
                <span>Mark as Featured Best MVC Project Award</span>
            </label>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded font-semibold">Publish to Archive</button>
        </form>

        @if(!empty($archivableTeams) && $archivableTeams->isNotEmpty())
        <div class="mt-6 pt-4 border-t">
            <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 mb-2">One-Click Archive from Active Team</h4>
            <div class="space-y-2 text-xs">
                @foreach($archivableTeams as $at)
                    <form method="POST" action="{{ route('archive.from_team', $at) }}" class="flex items-center justify-between p-2 border rounded bg-slate-50">
                        @csrf
                        <span class="font-semibold truncate pr-2">{{ $at->group_name }} (Sec {{ $at->section }})</span>
                        <button type="submit" class="bg-emerald-600 text-white px-2.5 py-1 rounded font-semibold">Archive Team</button>
                    </form>
                @endforeach
            </div>
        </div>
        @endif
        @else
        <div class="p-3 bg-slate-50 border rounded text-xs text-slate-600">
            All students and faculty can search and browse the Digital Showcase Repository. Publishing completed semester projects to the official archive is restricted to Supervisors and the Course Coordinator.
        </div>
        @endif
    </div>
    <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border">
        <form method="GET" action="{{ route('archive.index') }}" class="flex space-x-2 mb-4">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search title, stack, or keywords..." class="flex-1 border rounded px-3 py-1.5 text-sm">
            <input type="text" name="semester" value="{{ $semester }}" placeholder="Semester (e.g. Summer 2025)" class="border rounded px-3 py-1.5 text-sm w-48">
            <button type="submit" class="bg-slate-800 text-white px-4 py-1.5 rounded text-sm">Search</button>
        </form>
        @foreach($archives as $ar)
            <div class="p-4 border rounded-lg mb-3">
                <div class="flex justify-between items-center">
                    <div class="font-bold text-indigo-700">{{ $ar->project_title }} ({{ $ar->semester }})</div>
                    @if($ar->featured_award)
                        <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-0.5 rounded">Featured Award</span>
                    @endif
                </div>
                <div class="text-xs text-slate-500">{{ $ar->tech_stack }} • Team: {{ $ar->team_members_summary }} • Keywords: {{ $ar->keywords }}</div>
                <p class="text-sm mt-1">{{ $ar->abstract }}</p>
                @if($ar->github_repo_url)
                    <a href="{{ $ar->github_repo_url }}" target="_blank" class="text-xs text-indigo-600 underline mt-1 inline-block">GitHub Repository</a>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
