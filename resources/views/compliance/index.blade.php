@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Feature 17: Plagiarism & AI-Disclosure Compliance Tracker</h2>
<p class="text-sm text-slate-500 mb-6">Mandatory Section 8.1 AI-Use & Section 7 Third-Party Package Disclosure with automated plagiarism/topic overlap scanning.</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Submit Section 8.1 AI-Use & Ownership Statement</h3>
        @if(auth()->user()->role === 'student')
        <form method="POST" action="{{ route('compliance.store') }}" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block text-xs font-medium mb-1">My Project Group</label>
                <select name="team_id" class="w-full border rounded px-3 py-1.5" required>
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">AI Tools Used</label>
                <input type="text" name="ai_tools_used" value="{{ old('ai_tools_used', 'GitHub Copilot / Gemini (Debugging & Boilerplate Review)') }}" required class="w-full border rounded px-3 py-1.5">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">AI Usage Purpose Statement</label>
                <textarea name="ai_usage_purpose" rows="2" placeholder="Used for learning MVC patterns, debugging SQL foreign keys, and generating test cases." required class="w-full border rounded px-3 py-1.5">{{ old('ai_usage_purpose') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Influenced MVC Modules</label>
                <input type="text" name="influenced_modules" value="{{ old('influenced_modules', 'Blade UI styling and Jaccard similarity helper method') }}" required class="w-full border rounded px-3 py-1.5">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Third-Party Packages Used</label>
                <input type="text" name="third_party_packages" value="{{ old('third_party_packages', 'Laravel Framework 10.x, TailwindCSS CDN, FontAwesome Icons') }}" required class="w-full border rounded px-3 py-1.5">
            </div>
            <label class="flex items-start space-x-2 text-xs font-medium p-2.5 bg-slate-50 border rounded">
                <input type="checkbox" name="student_ownership_verified" value="1" class="mt-0.5" required>
                <span>We certify under CSE 470 Rule Book Section 8.1 that all core MVC architecture and business logic were written and understood by our 4 team members without plagiarism.</span>
            </label>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded font-semibold">Submit Compliance Disclosure</button>
        </form>
        @else
        <div class="p-3 bg-slate-50 border rounded text-xs text-slate-600">
            Student teams submit Section 8.1 AI-use and ownership disclosures here. Supervisors and Coordinators verify disclosures and audit plagiarism overlap scores on the right.
        </div>
        @endif
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Submitted Compliance & Plagiarism Audit Records</h3>
        @forelse($disclosures as $d)
            @php $scan = $plagiarismChecks[$d->id] ?? ['similarity_score' => 0, 'is_high_risk' => false, 'matched_archive_title' => 'None']; @endphp
            <div class="p-4 border rounded mb-3 text-sm">
                <div class="flex justify-between items-center">
                    <div class="font-bold">Tools: {{ $d->ai_tools_used }} ({{ $d->team->group_name ?? 'Team' }})</div>
                    @if($d->supervisor_verified)
                        <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2 py-0.5 rounded">Supervisor Verified</span>
                    @elseif(in_array(auth()->user()->role, ['supervisor', 'coordinator']))
                        <form method="POST" action="{{ route('compliance.verify', $d) }}">
                            @csrf
                            <button type="submit" class="bg-emerald-600 text-white px-2.5 py-1 rounded text-xs font-semibold">Verify Disclosure</button>
                        </form>
                    @else
                        <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-0.5 rounded">Pending Supervisor Review</span>
                    @endif
                </div>
                <p class="text-xs text-slate-600 mt-1"><strong>Purpose:</strong> {{ $d->ai_usage_purpose }}</p>
                <p class="text-xs text-slate-600"><strong>Influenced Modules:</strong> {{ $d->influenced_modules }}</p>
                <p class="text-xs text-slate-600"><strong>Third-Party Packages:</strong> {{ $d->third_party_packages }}</p>
                <div class="mt-2 pt-2 border-t space-y-1 text-xs">
                    <div class="flex justify-between items-center">
                        <span><strong>Corpus Overlap Scan:</strong> <span class="{{ $scan['is_high_risk'] ? 'text-rose-600 font-bold' : 'text-emerald-700 font-bold' }}">{{ $scan['similarity_score'] }}% ({{ $scan['is_high_risk'] ? 'HIGH RISK' : 'PASS' }})</span></span>
                        <span class="text-slate-400">Closest: {{ $scan['matched_archive_title'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span><strong>composer.json Dependency & Statement Audit:</strong></span>
                        <span class="font-bold px-2 py-0.5 rounded {{ ($scan['compliance_audit_score'] ?? 100) >= 75 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $scan['compliance_audit_score'] ?? 100 }} / 100
                        </span>
                    </div>
                    @if(!empty($scan['unmatched_packages']))
                        <div class="text-[11px] text-amber-700">
                            Unverified in composer.json: {{ implode(', ', $scan['unmatched_packages']) }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-xs text-slate-500">No disclosures submitted yet.</p>
        @endforelse
    </div>
</div>
@endsection
