<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\Proposal;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProposalQuotaAndDocumentPrivacyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_f3_pending_proposals_reserve_supervisor_quota_and_block_overflow(): void
    {
        $supervisor = User::create([
            'student_or_faculty_id' => 'FAC-QUOTA-01',
            'name' => 'Dr. Quota Supervisor',
            'email' => 'quota.supervisor@bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'supervisor',
            'department' => 'CSE',
            'section' => '09',
            'max_group_quota' => 1,
        ]);

        $studentA = User::create([
            'student_or_faculty_id' => '23990001',
            'name' => 'Student A',
            'email' => 'student.a@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);
        $teamA = Team::create([
            'group_name' => 'Quota Group A',
            'section' => '09',
            'leader_id' => $studentA->id,
            'status' => 'forming',
        ]);
        TeamMember::create(['team_id' => $teamA->id, 'student_id' => $studentA->id]);

        $studentB = User::create([
            'student_or_faculty_id' => '23990002',
            'name' => 'Student B',
            'email' => 'student.b@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);
        $teamB = Team::create([
            'group_name' => 'Quota Group B',
            'section' => '09',
            'leader_id' => $studentB->id,
            'status' => 'forming',
        ]);
        TeamMember::create(['team_id' => $teamB->id, 'student_id' => $studentB->id]);

        $this->assertSame(1, $supervisor->remainingSupervisorSlots());

        $twentyFeatures = implode("\n", array_map(fn ($i) => "Feature {$i}: Substantive MVC Module {$i}", range(1, 20)));

        // 1. Team A submits a pending proposal -> reserves the 1 available slot
        $this->actingAs($studentA)
            ->post('/proposals', [
                'team_id' => $teamA->id,
                'supervisor_id' => $supervisor->id,
                'title' => 'First Pending Proposal For Quota Test',
                'problem_statement' => 'Testing pending bid quota reservation.',
                'features_multiline' => $twentyFeatures,
                'tech_stack' => 'Laravel 10 (PHP) MVC + Blade + MySQL',
            ])
            ->assertSessionHas('success');

        $this->assertSame(0, $supervisor->fresh()->remainingSupervisorSlots());

        // 2. Team B tries to submit a pending proposal to the same supervisor -> rejected because quota is 0!
        $this->actingAs($studentB)
            ->post('/proposals', [
                'team_id' => $teamB->id,
                'supervisor_id' => $supervisor->id,
                'title' => 'Second Pending Proposal Exceeding Quota',
                'problem_statement' => 'Should be blocked by pending quota reservation.',
                'features_multiline' => $twentyFeatures,
                'tech_stack' => 'Laravel 10 (PHP) MVC + Blade + MySQL',
            ])
            ->assertSessionHas('error');

        $this->assertFalse(Proposal::where('team_id', $teamB->id)->exists());
    }

    public function test_f9_documents_index_only_loads_student_own_team_submissions(): void
    {
        $studentA = User::create([
            'student_or_faculty_id' => '23990003',
            'name' => 'Doc Student A',
            'email' => 'doc.a@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);
        $teamA = Team::create([
            'group_name' => 'Private Doc Team A',
            'section' => '09',
            'leader_id' => $studentA->id,
            'status' => 'locked',
        ]);
        TeamMember::create(['team_id' => $teamA->id, 'student_id' => $studentA->id]);

        $studentB = User::create([
            'student_or_faculty_id' => '23990004',
            'name' => 'Doc Student B',
            'email' => 'doc.b@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);
        $teamB = Team::create([
            'group_name' => 'Private Doc Team B',
            'section' => '09',
            'leader_id' => $studentB->id,
            'status' => 'locked',
        ]);
        TeamMember::create(['team_id' => $teamB->id, 'student_id' => $studentB->id]);

        DocumentSubmission::create([
            'team_id' => $teamA->id,
            'submitted_by' => $studentA->id,
            'document_type' => 'SRS',
            'version_tag' => 'v1.0',
            'file_path_or_drive_url' => 'https://docs.google.com/document/d/secret-team-a-srs',
            'changelog_notes' => 'Confidential Team A SRS',
            'faculty_review_status' => 'pending',
        ]);

        // Student B views /documents and must NOT see Team A's confidential submission
        $this->actingAs($studentB)
            ->get('/documents')
            ->assertStatus(200)
            ->assertDontSee('Confidential Team A SRS')
            ->assertDontSee('Private Doc Team A');

        // Student A views /documents and DOES see Team A's submission
        $this->actingAs($studentA)
            ->get('/documents')
            ->assertStatus(200)
            ->assertSee('Confidential Team A SRS');
    }

    public function test_report_improvements_f4_precheck_f15_command_and_f18_scope_sync(): void
    {
        $student = User::findOrFail(1);
        $coordinator = User::where('role', 'coordinator')->firstOrFail();

        // F1: Supervisor Match Score calculation
        $supervisor = User::where('role', 'supervisor')->firstOrFail();
        $match = $student->calculateSupervisorMatch($supervisor);
        $this->assertArrayHasKey('score', $match);
        $this->assertArrayHasKey('shared_tokens', $match);
        $this->assertGreaterThanOrEqual(0, $student->profileCompleteness());

        // F4 (defend): Pre-submit similarity check endpoint returns top matches and shared words
        $this->actingAs($student)
            ->post('/proposals/check-similarity', [
                'title' => 'WeHeal Integrated Hospital Telemedicine Portal',
                'problem_statement' => 'A hospital appointment and prescription workflow system following MVC pattern.',
            ])
            ->assertSessionHas('similarity_preview');

        // F15 (defend): Scheduled Artisan command runs cleanly
        $exitCode = \Illuminate\Support\Facades\Artisan::call('uninexus:send-deadline-reminders');
        $this->assertSame(0, $exitCode);

        // F18 (defend): Coordinator approval of stack_adjustment automatically updates Team::preferred_stack
        $team = Team::findOrFail(1);
        $scopeReq = \App\Models\ScopeChangeRequest::create([
            'team_id' => $team->id,
            'requested_by' => $student->id,
            'request_type' => 'stack_adjustment',
            'current_scope_detail' => $team->preferred_stack,
            'proposed_change_detail' => 'Laravel 10 MVC + Blade + MySQL + Chart.js',
            'reason_justification' => 'Adding Chart.js for F20 analytics visualizations.',
            'status' => 'pending',
        ]);

        $this->actingAs($coordinator)
            ->post("/disputes/{$scopeReq->id}/decide", [
                'status' => 'approved',
                'coordinator_decision_note' => 'Approved Chart.js addition.',
            ])
            ->assertSessionHas('success');

        $this->assertSame('Laravel 10 MVC + Blade + MySQL + Chart.js', $team->fresh()->preferred_stack);
    }
}
