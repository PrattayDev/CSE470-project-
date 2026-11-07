<?php

namespace App\Http\Controllers;

use App\Models\ProjectArchive;
use App\Models\Proposal;
use App\Models\Team;
use Illuminate\Http\Request;

/**
 * Feature 16: Project Showcase & Digital Archive Repository (Rownak)
 */
class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $semester = trim((string) $request->query('semester', ''));

        $archives = ProjectArchive::query()
            ->when($search !== '', function ($q) use ($search) {
                // Grouped OR conditions including abstract so they never escape the semester WHERE filter
                $q->where(function ($sub) use ($search) {
                    $sub->where('project_title', 'LIKE', "%{$search}%")
                        ->orWhere('keywords', 'LIKE', "%{$search}%")
                        ->orWhere('tech_stack', 'LIKE', "%{$search}%")
                        ->orWhere('abstract', 'LIKE', "%{$search}%");
                });
            })
            ->when($semester !== '', fn ($q) => $q->where('semester', $semester))
            ->latest('id')
            ->get();

        // Feature 16 (Defend): Rank results by weighted relevance score when a search query is provided
        if ($search !== '') {
            $term = strtolower($search);
            $archives = $archives->map(function (ProjectArchive $item) use ($term) {
                $score = 0;
                if (str_contains(strtolower((string) $item->project_title), $term)) {
                    $score += 4;
                }
                if (str_contains(strtolower((string) $item->keywords), $term)) {
                    $score += 3;
                }
                if (str_contains(strtolower((string) $item->tech_stack), $term)) {
                    $score += 2;
                }
                if (str_contains(strtolower((string) $item->abstract), $term)) {
                    $score += 1;
                }
                $item->relevance_score = $score;
                return $item;
            })->sortByDesc('relevance_score')->values();
        }

        $archivableTeams = Team::with(['members.student'])->whereIn('status', ['supervised', 'cleared'])->get();

        return view('archive.index', compact('archives', 'search', 'semester', 'archivableTeams'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null && in_array($user->role, ['supervisor', 'coordinator'], true), 403);

        $validated = $request->validate([
            'project_title' => 'required|string|max:255',
            'semester' => 'required|string|max:40',
            'team_members_summary' => 'required|string|max:255',
            'tech_stack' => 'required|string|max:120',
            'abstract' => 'required|string',
            'github_repo_url' => 'nullable|url|max:255',
            'keywords' => 'required|string|max:255',
            'featured_award' => 'nullable|boolean',
        ]);

        ProjectArchive::create([
            ...$validated,
            'featured_award' => $request->boolean('featured_award'),
        ]);

        return back()->with('success', 'Feature 16: Project archived in Digital Showcase Repository.');
    }

    /**
     * Feature 16 (Defend): Auto-archive an approved/cleared team's project proposal into the showcase repository.
     */
    public function archiveTeam(Request $request, Team $team)
    {
        $user = $request->user();
        abort_unless($user !== null && in_array($user->role, ['supervisor', 'coordinator'], true), 403);

        $proposal = Proposal::where('team_id', $team->id)
            ->where('status', 'approved')
            ->latest('id')
            ->first();

        if (!$proposal) {
            return back()->with('error', 'Feature 16 Rejected: Team must have an approved proposal before auto-archiving.');
        }

        $team->load('members.student');
        $memberNames = $team->members->map(fn ($m) => $m->student->name ?? 'Student')->implode(', ');
        $summary = $team->group_name . ' (' . ($memberNames ?: '4 Members') . ')';

        $words = preg_split('/[^a-zA-Z0-9]+/', strtolower($proposal->title)) ?: [];
        $keywords = implode(' ', array_slice(array_values(array_filter($words, fn ($w) => strlen($w) > 3)), 0, 8));

        ProjectArchive::updateOrCreate(
            ['project_title' => $proposal->title],
            [
                'semester' => $request->input('semester', 'Fall 2026'),
                'team_members_summary' => substr($summary, 0, 250),
                'tech_stack' => $proposal->tech_stack,
                'abstract' => $proposal->problem_statement,
                'github_repo_url' => 'https://github.com/cse470-group3/uninexus',
                'keywords' => $keywords ?: 'laravel mvc software engineering',
                'featured_award' => $request->boolean('featured_award'),
            ]
        );

        return back()->with('success', "Feature 16: Team '{$team->group_name}' approved proposal automatically archived into Digital Showcase.");
    }
}
