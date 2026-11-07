<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\ProjectArchive;

/**
 * Feature 4: Duplicate Topic & Title Similarity Checker
 * Computes Jaccard & Token Overlap Similarity against existing Proposals and Archives.
 */
class TopicSimilarityService
{
    private array $stopWords = [
        'a', 'an', 'the', 'and', 'or', 'for', 'of', 'in', 'on', 'to', 'with',
        'system', 'management', 'platform', 'application', 'web', 'based', 'using'
    ];

    public function analyzeProposalSimilarity(string $title, string $problemStatement, ?int $excludeTeamId = null): array
    {
        $inputTokens = $this->tokenize($title . ' ' . $problemStatement);
        $maxSimilarity = 0.0;
        $matchedTitle = 'None (100% Unique)';
        $bestSharedWords = [];
        $candidates = [];

        // 1. Compare with existing current-semester Proposals
        $proposals = Proposal::query()
            ->when($excludeTeamId, fn ($q) => $q->where('team_id', '!=', $excludeTeamId))
            ->get();

        foreach ($proposals as $proposal) {
            $candidateTokens = $this->tokenize($proposal->title . ' ' . $proposal->problem_statement);
            $score = $this->jaccardSimilarity($inputTokens, $candidateTokens);
            $shared = array_values(array_intersect($inputTokens, $candidateTokens));
            $pct = round($score * 100, 2);
            $label = '[Current Proposal] ' . $proposal->title;

            $candidates[] = [
                'title' => $label,
                'similarity_score' => $pct,
                'shared_words' => $shared,
            ];

            if ($score > $maxSimilarity) {
                $maxSimilarity = $score;
                $matchedTitle = $label;
                $bestSharedWords = $shared;
            }
        }

        // 2. Compare with Past Semester Project Archives
        $archives = ProjectArchive::all();
        foreach ($archives as $archive) {
            $candidateTokens = $this->tokenize($archive->project_title . ' ' . $archive->abstract . ' ' . $archive->keywords);
            $score = $this->jaccardSimilarity($inputTokens, $candidateTokens);
            $shared = array_values(array_intersect($inputTokens, $candidateTokens));
            $pct = round($score * 100, 2);
            $label = '[Archive ' . $archive->semester . '] ' . $archive->project_title;

            $candidates[] = [
                'title' => $label,
                'similarity_score' => $pct,
                'shared_words' => $shared,
            ];

            if ($score > $maxSimilarity) {
                $maxSimilarity = $score;
                $matchedTitle = $label;
                $bestSharedWords = $shared;
            }
        }

        usort($candidates, fn ($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);
        $topMatches = array_slice($candidates, 0, 3);
        $finalScore = round($maxSimilarity * 100, 2);

        return [
            'similarity_score' => $finalScore,
            'matched_archive_title' => $matchedTitle,
            'is_high_risk' => $finalScore >= 45.0,
            'risk_band' => $finalScore >= 45.0 ? 'high' : ($finalScore >= 25.0 ? 'moderate' : 'low'),
            'shared_words' => $bestSharedWords,
            'top_matches' => $topMatches,
        ];
    }

    private function tokenize(string $text): array
    {
        $cleaned = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $text));
        $words = array_filter(explode(' ', $cleaned), function ($word) {
            return strlen($word) > 2 && !in_array($word, $this->stopWords);
        });
        return array_values(array_unique($words));
    }

    private function jaccardSimilarity(array $tokensA, array $tokensB): float
    {
        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }
        $intersection = count(array_intersect($tokensA, $tokensB));
        $union = count(array_unique(array_merge($tokensA, $tokensB)));
        return $union > 0 ? ($intersection / $union) : 0.0;
    }
}
