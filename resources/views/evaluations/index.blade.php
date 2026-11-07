@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Features 10 & 11: CSE 470 Rubric Viva Grading (15 Marks) & Peer Weightage</h2>
<p class="text-sm text-slate-500 mb-6">Official Section 15 Marking Rubric: SRS & Sprint (2) + MVC Implementation (3) + Individual Viva (10) = 15 Marks.</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    @if(in_array(auth()->user()->role ?? '', ['supervisor', 'coordinator'], true))
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Feature 10: Faculty Rubric & Viva Grading</h3>
        <form method="POST" action="{{ route('evaluations.rubric.store') }}" class="space-y-3 text-sm">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-medium mb-1">Select Team</label>
                    <select name="team_id" class="w-full border rounded px-3 py-1.5">
                        @foreach($teams as $t)
                            <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Select Student</label>
                    <select name="student_id" class="w-full border rounded px-3 py-1.5">
                        @foreach($students as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->student_or_faculty_id }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-xs font-medium mb-1">SRS & Sprint (Max 2)</label>
                    <input type="number" step="0.25" max="2" min="0" name="srs_and_sprint_marks" value="2.00" required class="w-full border rounded px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">MVC & Quality (Max 3)</label>
                    <input type="number" step="0.25" max="3" min="0" name="mvc_implementation_marks" value="2.75" required class="w-full border rounded px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Viva Q&A (Max 10)</label>
                    <input type="number" step="0.5" max="10" min="0" name="individual_viva_marks" value="9.00" required class="w-full border rounded px-2 py-1.5">
                </div>
            </div>
            <input type="text" name="viva_remarks" placeholder="Defended MVC controller flow and Git commits clearly." class="w-full border rounded px-3 py-1.5">
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded font-semibold">Compute & Save 15-Mark Grade</button>
        </form>
    </div>
    @endif

    @if((auth()->user()->role ?? '') === 'student')
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Feature 11: Peer Contribution Evaluation</h3>
        <form method="POST" action="{{ route('evaluations.peer.store') }}" class="space-y-3 text-sm">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-medium mb-1">Select Team</label>
                    <select name="team_id" class="w-full border rounded px-3 py-1.5">
                        @foreach($teams as $t)
                            <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Teammate Being Evaluated</label>
                    <select name="evaluatee_student_id" class="w-full border rounded px-3 py-1.5">
                        @foreach($students as $s)
                            @if($s->id !== auth()->id())
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-xs font-medium mb-1">Coding (1-10)</label>
                    <input type="number" min="1" max="10" name="coding_contribution_score" value="10" class="w-full border rounded px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Sprint (1-10)</label>
                    <input type="number" min="1" max="10" name="sprint_reliability_score" value="9" class="w-full border rounded px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">SRS Docs (1-10)</label>
                    <input type="number" min="1" max="10" name="documentation_score" value="10" class="w-full border rounded px-2 py-1.5">
                </div>
            </div>
            <input type="text" name="justification" placeholder="Completed all 5 assigned MVC features on time." required class="w-full border rounded px-3 py-1.5">
            <button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded font-semibold">Submit Peer Score</button>
        </form>
    </div>
    @endif
</div>

<div class="bg-white p-6 rounded-xl shadow-sm border">
    <h3 class="font-bold mb-3">Student Rubric Grades & Team-Normalized Peer Weightage Summary</h3>
    <table class="w-full text-left text-sm border">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-2.5">Student</th>
                <th class="p-2.5">SRS + Sprint (2)</th>
                <th class="p-2.5">MVC Quality (3)</th>
                <th class="p-2.5">Viva (10)</th>
                <th class="p-2.5">Total (15)</th>
                <th class="p-2.5">Normalized Peer Weightage</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($students as $stu)
                @php
                    $rub = $rubrics->firstWhere('student_id', $stu->id);
                    $w = $weightageMap[$stu->id] ?? ['review_count' => 0, 'contribution_percentage' => 0.0, 'normalized_team_share' => 0.0];
                @endphp
                <tr>
                    <td class="p-2.5 font-semibold">{{ $stu->name }} ({{ $stu->student_or_faculty_id }})</td>
                    <td class="p-2.5">{{ $rub->srs_and_sprint_marks ?? '-' }}</td>
                    <td class="p-2.5">{{ $rub->mvc_implementation_marks ?? '-' }}</td>
                    <td class="p-2.5">{{ $rub->individual_viva_marks ?? '-' }}</td>
                    <td class="p-2.5 font-bold text-indigo-700">
                        {{ $rub->total_marks ?? 'Pending' }} / 15.00
                        @if($rub && $rub->is_finalized)
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] uppercase font-bold">Locked</span>
                        @elseif($rub && (auth()->user()->role ?? '') === 'coordinator')
                            <form method="POST" action="{{ route('evaluations.rubric.finalize', $rub) }}" class="inline ml-2">
                                @csrf
                                <button type="submit" class="bg-slate-800 text-white text-[10px] px-2 py-0.5 rounded font-semibold">Finalize & Lock</button>
                            </form>
                        @endif
                    </td>
                    <td class="p-2.5 font-semibold text-emerald-700">
                        @if(($w['review_count'] ?? 0) === 0)
                            <span class="text-slate-400">0.0% (No reviews)</span>
                        @else
                            {{ $w['contribution_percentage'] }}% (Team Share: {{ $w['normalized_team_share'] ?? 0 }}%)
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
