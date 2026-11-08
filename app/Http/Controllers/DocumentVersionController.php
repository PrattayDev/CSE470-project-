<?php

namespace App\Http\Controllers;

use App\Models\DocumentSubmission;
use App\Models\Proposal;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Feature 9: Document Version Control & SRS/Report Submission Portal (Rownak - Sprint 1)
 */
class DocumentVersionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if ($user->role === 'student') {
            $teams = Team::whereHas('members', fn ($q) => $q->where('student_id', $user->id))
                ->orWhere('leader_id', $user->id)
                ->get();
            $teamIds = $teams->pluck('id')->toArray();

            $documents = DocumentSubmission::with(['team', 'submitter'])
                ->whereIn('team_id', $teamIds)
                ->latest('id')
                ->get();
        } elseif ($user->role === 'supervisor') {
            $supervisedTeamIds = Team::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $proposalTeamIds = Proposal::where('supervisor_id', $user->id)
                ->whereIn('status', ['pending', 'revision_required', 'approved'])
                ->pluck('team_id')
                ->toArray();
            $allowedTeamIds = array_values(array_unique(array_merge($supervisedTeamIds, $proposalTeamIds)));

            $teams = Team::whereIn('id', $allowedTeamIds)->get();
            $documents = DocumentSubmission::with(['team', 'submitter'])
                ->whereIn('team_id', $allowedTeamIds)
                ->latest('id')
                ->get();
        } else {
            // Course Coordinator or authorized faculty oversight
            $teams = Team::all();
            $documents = DocumentSubmission::with(['team', 'submitter'])
                ->latest('id')
                ->get();
        }

        return view('documents.index', compact('documents', 'teams', 'user'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null && $user->role === 'student', 403);

        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'document_type' => 'required|in:SRS,UML_Class_Diagram,Sprint_Report,Final_Report',
            'version_tag' => ['required', 'string', 'max:20', 'regex:/^v\d+\.\d+(\.\d+)?$/i'],
            'document_file' => 'nullable|file|mimes:pdf,doc,docx,md,txt,png,jpg,jpeg,zip|max:10240|required_without:file_path_or_drive_url',
            'file_path_or_drive_url' => 'nullable|url|max:255|required_without:document_file',
            'changelog_notes' => 'required|string|max:1000',
        ], [
            'version_tag.regex' => 'Version tag must follow semantic format such as v1.0, v1.1, or v2.0.',
            'document_file.required_without' => 'Please upload a document file or provide a valid Drive/repository URL.',
            'file_path_or_drive_url.required_without' => 'Please provide a valid Drive/repository URL or upload a document file.',
        ]);

        $teamId = (int) $validated['team_id'];

        // Enforce Team Ownership: student must belong to the selected team
        $belongsToTeam = $this->studentBelongsToTeam($user->id, $teamId);

        if (!$belongsToTeam) {
            return back()
                ->withErrors(['team_id' => 'Unauthorized: You can only commit document versions for your own assigned team.'])
                ->withInput();
        }

        // Enforce Sequential Semantic Version Tag Progression per (team_id, document_type)
        $normalizedTag = 'v' . ltrim(trim($validated['version_tag']), 'vV');
        $newVersionNumber = ltrim($normalizedTag, 'v');

        $existingVersions = DocumentSubmission::where('team_id', $teamId)
            ->where('document_type', $validated['document_type'])
            ->pluck('version_tag');

        $highestVersion = null;
        foreach ($existingVersions as $tag) {
            $cleanExisting = ltrim(trim((string) $tag), 'vV');
            if ($cleanExisting === $newVersionNumber) {
                return back()
                    ->withErrors(['version_tag' => "Version {$normalizedTag} already exists for {$validated['document_type']}."])
                    ->withInput();
            }
            if ($highestVersion === null || version_compare($cleanExisting, $highestVersion, '>')) {
                $highestVersion = $cleanExisting;
            }
        }

        if ($highestVersion !== null && !version_compare($newVersionNumber, $highestVersion, '>')) {
            return back()
                ->withErrors([
                    'version_tag' => "Version sequence error: New version ({$normalizedTag}) must be greater than the latest version (v{$highestVersion})."
                ])
                ->withInput();
        }

        // Store uploaded files in private storage (never under public/)
        $storedPathOrUrl = $validated['file_path_or_drive_url'] ?? '';
        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $privateDir = storage_path('app/private/documents');
            if (!is_dir($privateDir)) {
                mkdir($privateDir, 0755, true);
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $safeName = 'team' . $teamId . '_' . strtolower($validated['document_type']) . '_' . str_replace('.', '_', $normalizedTag) . '_' . uniqid() . '.' . $ext;
            $file->move($privateDir, $safeName);
            $storedPathOrUrl = 'private/documents/' . $safeName;
        }

        DocumentSubmission::create([
            'team_id' => $teamId,
            'submitted_by' => $user->id,
            'document_type' => $validated['document_type'],
            'version_tag' => $normalizedTag,
            'file_path_or_drive_url' => $storedPathOrUrl,
            'changelog_notes' => $validated['changelog_notes'],
            'faculty_review_status' => 'pending',
        ]);

        return back()->with('success', "Feature 9: Document {$validated['document_type']} ({$normalizedTag}) committed to private team repository.");
    }

    public function download(Request $request, DocumentSubmission $document)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if (!$this->canAccessDocument($user, $document)) {
            abort(403, 'Access denied: You are only authorized to access your own team or supervised team documents.');
        }

        $target = (string) $document->file_path_or_drive_url;

        if (str_starts_with($target, 'private/documents/')) {
            $fullPath = storage_path('app/' . $target);
            if (!is_file($fullPath)) {
                abort(404, 'Uploaded document file not found in private storage.');
            }
            return response()->download($fullPath, basename($fullPath));
        }

        if (filter_var($target, FILTER_VALIDATE_URL)) {
            return redirect()->away($target);
        }

        abort(404, 'Document artifact not found.');
    }

    public function review(Request $request, DocumentSubmission $document)
    {
        $user = $request->user();
        abort_unless($user !== null && in_array($user->role, ['supervisor', 'coordinator'], true), 403);

        if ($user->role === 'supervisor' && !$this->canAccessDocument($user, $document)) {
            return back()->with('error', 'Access denied: Only the assigned team supervisor or course coordinator can review this document.');
        }

        $validated = $request->validate([
            'faculty_review_status' => 'required|in:approved,needs_update',
            'faculty_comments' => 'required|string|max:1000',
        ]);

        $document->update($validated);
        return back()->with('success', 'Feature 9: Faculty document review status updated.');
    }

    private function studentBelongsToTeam(int $userId, int $teamId): bool
    {
        return TeamMember::where('team_id', $teamId)
            ->where('student_id', $userId)
            ->exists()
            || Team::where('id', $teamId)->where('leader_id', $userId)->exists();
    }

    private function canAccessDocument(User $user, DocumentSubmission $document): bool
    {
        if ($user->role === 'coordinator') {
            return true;
        }

        if ($user->role === 'student') {
            return $this->studentBelongsToTeam((int) $user->id, (int) $document->team_id);
        }

        if ($user->role === 'supervisor') {
            $team = $document->team;
            if ($team && (int) $team->supervisor_id === (int) $user->id) {
                return true;
            }
            return Proposal::where('team_id', $document->team_id)
                ->where('supervisor_id', $user->id)
                ->whereIn('status', ['pending', 'revision_required', 'approved'])
                ->exists();
        }

        return false;
    }
}
