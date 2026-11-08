<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Feature 1: Multi-Role Academic Profile & Skill Matrix Management (Prattay - Sprint 1)
 */
class ProfileSkillController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $skillFilter = trim((string) $request->query('skill', ''));
        $roleFilter = trim((string) $request->query('role', ''));
        $sectionFilter = trim((string) $request->query('section', ''));

        $directory = User::query()
            ->when(in_array($roleFilter, ['student', 'supervisor', 'lab_admin', 'coordinator'], true), function ($q) use ($roleFilter) {
                $q->where('role', $roleFilter);
            })
            ->when($sectionFilter !== '', function ($q) use ($sectionFilter) {
                $q->where('section', $sectionFilter);
            })
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->filter(function (User $person) use ($skillFilter) {
                if ($skillFilter === '') {
                    return true;
                }
                $target = strtolower($skillFilter);
                foreach ($person->skills ?? [] as $tag) {
                    $normalizedTag = strtolower(trim((string) $tag));
                    if ($normalizedTag === $target) {
                        return true;
                    }
                    // Also allow matching a distinct word inside a multi-word skill tag (e.g. "Blade" in "Blade UI"), while preventing "C" from matching "CSS"
                    $words = preg_split('/[\s\/\-]+/', $normalizedTag) ?: [];
                    if (in_array($target, $words, true)) {
                        return true;
                    }
                }
                return false;
            })
            ->values();

        // Feature 1 (Defend): Supervisor Match Score Ranking comparing student skills/interests against supervisors
        $supervisors = User::whereIn('role', ['supervisor', 'coordinator'])->get();
        $supervisorMatches = $supervisors->map(function (User $sup) use ($user) {
            $match = $user->calculateSupervisorMatch($sup);
            return [
                'supervisor' => $sup,
                'score' => $match['score'],
                'matched_terms' => $match['matched_terms'],
                'shared_tokens' => $match['shared_tokens'],
                'remaining_slots' => $sup->remainingSupervisorSlots(),
            ];
        })->sortByDesc('score')->values();

        return view('profile.index', compact('user', 'directory', 'skillFilter', 'roleFilter', 'sectionFilter', 'supervisorMatches'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $canManageQuota = in_array($user->role, ['supervisor', 'coordinator'], true);
        $isStudent = $user->role === 'student';

        $rules = [
            'name' => 'required|string|max:120',
            'section' => 'nullable|string|max:20',
            'cgpa' => $isStudent ? 'nullable|numeric|between:0,4.00' : 'prohibited',
            'skills_csv' => 'required|string|max:500',
            'research_interests' => 'nullable|string|max:500',
            'github_username' => 'nullable|string|max:100',
            'max_group_quota' => $canManageQuota ? 'required|integer|min:1|max:20' : 'prohibited',
        ];

        $validated = $request->validate($rules);

        $skillsArray = array_values(array_unique(array_filter(array_map('trim', explode(',', $validated['skills_csv'])))));
        if (empty($skillsArray)) {
            return back()->withErrors(['skills_csv' => 'At least one valid technical skill tag is required.'])->withInput();
        }

        $payload = [
            'name' => $validated['name'],
            'section' => $validated['section'] ?? $user->section,
            'skills' => $skillsArray,
            'research_interests' => $validated['research_interests'] ?? null,
            'github_username' => $validated['github_username'] ?? null,
        ];

        if ($isStudent) {
            $payload['cgpa'] = $validated['cgpa'] ?? null;
            $payload['max_group_quota'] = 0;
        } elseif ($canManageQuota) {
            $payload['cgpa'] = null;
            $payload['max_group_quota'] = (int) $validated['max_group_quota'];
        }

        $user->update($payload);

        return back()->with('success', 'Feature 1: Academic Profile & Skill Matrix updated successfully.');
    }
}
