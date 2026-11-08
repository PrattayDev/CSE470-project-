@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Feature 9: Document Version Control & SRS Submission Portal</h2>
<p class="text-sm text-slate-500 mb-6">Track sequential versions (e.g. v1.0 → v1.1) of the SRS document, UML Class Diagram (Assignment 1), and Sprint reports with file upload or faculty-shared Drive links.</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Commit New Document Version</h3>
        @if(auth()->user()->role === 'student')
            @if($teams->isEmpty())
                <p class="text-xs text-rose-600">You must join or create a 4-member project group before submitting team documents.</p>
            @else
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-3 text-sm">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium mb-1">My Project Group</label>
                        <select name="team_id" class="w-full border rounded px-3 py-2" required>
                            @foreach($teams as $t)
                                <option value="{{ $t->id }}" @selected(old('team_id') == $t->id)>{{ $t->group_name }} (Sec {{ $t->section }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Document Type</label>
                        <select name="document_type" class="w-full border rounded px-3 py-2" required>
                            <option value="SRS" @selected(old('document_type') === 'SRS')>SRS Document (Faculty Shared)</option>
                            <option value="UML_Class_Diagram" @selected(old('document_type') === 'UML_Class_Diagram')>UML Class Diagram (Assignment 1)</option>
                            <option value="Sprint_Report" @selected(old('document_type') === 'Sprint_Report')>Sprint Progress Report</option>
                            <option value="Final_Report" @selected(old('document_type') === 'Final_Report')>Final Documentation</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Sequential Version Tag (e.g. v1.0, v1.1, v2.0)</label>
                        <input type="text" name="version_tag" value="{{ old('version_tag', 'v1.1') }}" placeholder="v1.1" required class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Upload Document File (PDF/DOCX/PNG/ZIP)</label>
                        <input type="file" name="document_file" accept=".pdf,.doc,.docx,.md,.txt,.png,.jpg,.jpeg,.zip" class="w-full border rounded px-2 py-1.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Or Faculty-Shared Google Drive Link</label>
                        <input type="url" name="file_path_or_drive_url" value="{{ old('file_path_or_drive_url') }}" placeholder="https://docs.google.com/document/d/..." class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Version Changelog Notes</label>
                        <textarea name="changelog_notes" rows="3" placeholder="Added 20 substantive features and MVC mapping..." required class="w-full border rounded px-3 py-2">{{ old('changelog_notes') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded font-semibold">Commit Document Version</button>
                </form>
            @endif
        @else
            <div class="p-3 bg-slate-50 border rounded text-xs text-slate-600">
                Logged in as <strong class="uppercase">{{ auth()->user()->role }}</strong>. Student group members submit new document versions; Supervisors and Coordinators review and approve versions on the right.
            </div>
        @endif
    </div>

    <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Version History & Faculty Feedback</h3>
        <div class="space-y-3 text-sm">
            @forelse($documents as $doc)
            @php
                $docNext = match($doc->faculty_review_status) {
                    'pending' => 'Awaiting Faculty Review',
                    'needs_update' => 'Upload Next Version',
                    default => null,
                };
            @endphp
            <div class="p-4 border rounded-lg">
                <div class="flex flex-wrap justify-between items-start gap-2">
                    <div>
                        <span class="font-bold">{{ $doc->document_type }} ({{ $doc->version_tag }})</span>
                        <div class="text-xs text-slate-500">Team: {{ $doc->team->group_name ?? 'Team' }} • Committed by: {{ $doc->submitter->name ?? 'Student' }}</div>
                    </div>
                    <x-status-badge :status="$doc->faculty_review_status" :nextAction="$docNext" />
                </div>
                <p class="text-xs text-slate-600 mt-1.5"><strong>Changelog:</strong> {{ $doc->changelog_notes }}</p>
                <a href="{{ route('documents.download', $doc) }}" target="_blank" class="text-xs text-indigo-600 underline inline-block mt-1">Open / Download Protected Document Artifact</a>
                @if($doc->faculty_comments)
                    <div class="mt-2 p-2 bg-emerald-50 text-emerald-800 rounded text-xs">
                        <strong>Faculty Review:</strong> {{ $doc->faculty_comments }}
                    </div>
                @endif
                @if(in_array(auth()->user()->role, ['supervisor', 'coordinator']))
                <form method="POST" action="{{ route('documents.review', $doc) }}" class="mt-3 pt-2 border-t flex space-x-2 text-xs">
                    @csrf
                    <select name="faculty_review_status" class="border rounded px-2 py-1">
                        <option value="approved" @selected($doc->faculty_review_status === 'approved')>Approve Version</option>
                        <option value="needs_update" @selected($doc->faculty_review_status === 'needs_update')>Needs Update</option>
                    </select>
                    <input type="text" name="faculty_comments" value="{{ $doc->faculty_comments }}" placeholder="Faculty review comments..." required class="flex-1 border rounded px-2 py-1">
                    <button type="submit" class="bg-slate-800 text-white px-3 py-1 rounded">Save Review</button>
                </form>
                @endif
            </div>
            @empty
            <p class="text-xs text-slate-500">No documents submitted yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
