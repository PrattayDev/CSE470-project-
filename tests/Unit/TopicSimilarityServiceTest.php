<?php

namespace Tests\Unit;

use App\Services\TopicSimilarityService;
use Tests\TestCase;

class TopicSimilarityServiceTest extends TestCase
{
    public function test_similarity_detects_high_overlap_against_hospital_archive(): void
    {
        $service = new TopicSimilarityService();

        $result = $service->analyzeProposalSimilarity(
            'WeHeal Integrated Hospital Telemedicine Portal',
            'A hospital appointment and prescription workflow system following MVC pattern with doctor telemedicine.',
            999
        );

        $this->assertGreaterThanOrEqual(45.0, $result['similarity_score']);
        $this->assertTrue($result['is_high_risk']);
        $this->assertStringContainsString('WeHeal', $result['matched_archive_title']);
    }

    public function test_similarity_returns_low_score_for_unique_topic(): void
    {
        $service = new TopicSimilarityService();

        $result = $service->analyzeProposalSimilarity(
            'Quantum Cryptography Key Distribution Simulator',
            'Simulates BB84 photon polarization protocols and quantum bit error rate calculation.',
            999
        );

        $this->assertLessThan(45.0, $result['similarity_score']);
        $this->assertFalse($result['is_high_risk']);
    }

    public function test_similarity_handles_empty_or_stopword_only_inputs(): void
    {
        $service = new TopicSimilarityService();

        $result = $service->analyzeProposalSimilarity(
            'the and or for in on to with',
            'system management platform web based application',
            999
        );

        $this->assertSame(0.0, $result['similarity_score']);
        $this->assertFalse($result['is_high_risk']);
    }
}
