<?php

namespace Tests\Feature;

use App\Models\ComplianceDisclosure;
use App\Models\ScopeChangeRequest;
use App\Models\Sprint;
use App\Models\SystemNotification;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RownakAndAnalyticsFeaturesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_f15_notifications_scoped_to_user_and_mark_read_enforces_ownership(): void
    {
        $student1 = User::findOrFail(1);
        $student2 = User::findOrFail(2);
        $supervisor = User::findOrFail(5);

        // Supervisor broadcasts reminder
        $this->actingAs($supervisor)
            ->post('/notifications/broadcast', [
                'title' => 'Friday Sprint 2 Deadline Reminder',
                'message' => 'Submit PR and demo link by 11:59 PM.',
                'type' => 'deadline',
            ])
            ->assertSessionHas('success');

        $notifForStudent2 = SystemNotification::where('user_id', $student2->id)
            ->where('title', 'Friday Sprint 2 Deadline Reminder')
            ->firstOrFail();

        // Student 1 cannot mark Student 2's notification as read
        $this->actingAs($student1)
            ->post("/notifications/{$notifForStudent2->id}/read")
            ->assertSessionHas('error');

        $this->assertFalse((bool) $notifForStudent2->fresh()->is_read);

        // Student 2 can mark their own notification as read
        $this->actingAs($student2)
            ->post("/notifications/{$notifForStudent2->id}/read")
            ->assertSessionHas('success');

        $this->assertTrue((bool) $notifForStudent2->fresh()->is_read);
    }

    public function test_f16_archive_search_respects_semester_filter_and_blocks_students_from_publishing(): void
    {
        $student = User::findOrFail(1);

        // Query 'portal' (in Summer 2025 WeHeal) while filtering semester 'Spring 2026' -> must not leak Summer 2025 row
        $this->actingAs($student)
            ->get('/archive?q=portal&semester=Spring+2026')
            ->assertStatus(200)
            ->assertDontSee('WeHeal - Integrated Hospital Telemedicine Portal');

        // Student blocked from publishing to official archive
        $this->actingAs($student)
            ->post('/archive', [
                'project_title' => 'Unauthorized Archive Entry',
                'semester' => 'Spring 2026',
                'team_members_summary' => '4 Members',
                'tech_stack' => 'Laravel MVC + MySQL',
                'abstract' => 'Test abstract',
                'keywords' => 'laravel mvc',
            ])
            ->assertSessionHas('error');
    }

    public function test_f17_compliance_page_scopes_disclosures_to_student_team_and_requires_explicit_ownership(): void
    {
        $studentA = User::create([
            'student_or_faculty_id' => '23991001',
            'name' => 'Compliance Student A',
            'email' => 'comp.a@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);
        $teamA = Team::create([
            'group_name' => 'Compliance Team A',
            'section' => '09',
            'leader_id' => $studentA->id,
            'status' => 'locked',
        ]);
        TeamMember::create(['team_id' => $teamA->id, 'student_id' => $studentA->id]);

        $studentB = User::create([
            'student_or_faculty_id' => '23991002',
            'name' => 'Compliance Student B',
            'email' => 'comp.b@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);
        $teamB = Team::create([
            'group_name' => 'Compliance Team B',
            'section' => '09',
            'leader_id' => $studentB->id,
            'status' => 'locked',
        ]);
        TeamMember::create(['team_id' => $teamB->id, 'student_id' => $studentB->id]);

        // Missing student_ownership_verified checkbox is rejected
        $this->actingAs($studentA)
            ->post('/compliance', [
                'team_id' => $teamA->id,
                'ai_tools_used' => 'GitHub Copilot',
                'ai_usage_purpose' => 'Boilerplate Blade syntax',
                'influenced_modules' => 'F17 View',
                'third_party_packages' => 'laravel/framework',
            ])
            ->assertSessionHasErrors('student_ownership_verified');

        // Valid disclosure by Team A
        $this->actingAs($studentA)
            ->post('/compliance', [
                'team_id' => $teamA->id,
                'ai_tools_used' => 'Secret AI Tool Team A',
                'ai_usage_purpose' => 'Confidential Team A disclosure notes',
                'influenced_modules' => 'F17 Module',
                'third_party_packages' => 'laravel/framework',
                'student_ownership_verified' => '1',
            ])
            ->assertSessionHas('success');

        // Student B must NOT see Team A's disclosure on /compliance
        $this->actingAs($studentB)
            ->get('/compliance')
            ->assertStatus(200)
            ->assertDontSee('Confidential Team A disclosure notes');

        // Student A DOES see Team A's disclosure
        $this->actingAs($studentA)
            ->get('/compliance')
            ->assertStatus(200)
            ->assertSee('Confidential Team A disclosure notes');
    }

    public function test_f18_disputes_scoped_to_student_team_and_decided_only_by_coordinator(): void
    {
        $studentA = User::create([
            'student_or_faculty_id' => '23992001',
            'name' => 'Dispute Student A',
            'email' => 'disp.a@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);
        $teamA = Team::create([
            'group_name' => 'Dispute Team A',
            'section' => '09',
            'leader_id' => $studentA->id,
            'status' => 'locked',
        ]);
        TeamMember::create(['team_id' => $teamA->id, 'student_id' => $studentA->id]);

        $studentB = User::create([
            'student_or_faculty_id' => '23992002',
            'name' => 'Dispute Student B',
            'email' => 'disp.b@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);
        $teamB = Team::create([
            'group_name' => 'Dispute Team B',
            'section' => '09',
            'leader_id' => $studentB->id,
            'status' => 'locked',
        ]);
        TeamMember::create(['team_id' => $teamB->id, 'student_id' => $studentB->id]);

        $this->actingAs($studentA)
            ->post('/disputes', [
                'team_id' => $teamA->id,
                'request_type' => 'member_dispute',
                'current_scope_detail' => 'Private Team A internal scope',
                'proposed_change_detail' => 'Reassign F18 task',
                'reason_justification' => 'Confidential Team A dispute reason',
            ])
            ->assertSessionHas('success');

        $ticket = ScopeChangeRequest::where('team_id', $teamA->id)->firstOrFail();

        // Student B must NOT see Team A's private dispute ticket
        $this->actingAs($studentB)
            ->get('/disputes')
            ->assertStatus(200)
            ->assertDontSee('Confidential Team A dispute reason');

        // Student A DOES see Team A's dispute ticket
        $this->actingAs($studentA)
            ->get('/disputes')
            ->assertStatus(200)
            ->assertSee('Confidential Team A dispute reason');

        // Coordinator can see and resolve the dispute ticket
        $coordinator = User::where('role', 'coordinator')->firstOrFail();
        $this->actingAs($coordinator)
            ->get('/disputes')
            ->assertStatus(200)
            ->assertSee('Confidential Team A dispute reason');

        $this->actingAs($coordinator)
            ->post("/disputes/{$ticket->id}/decide", [
                'status' => 'approved',
                'coordinator_decision_note' => 'Approved after coordinator hearing.',
            ])
            ->assertSessionHas('success');

        $this->assertSame('approved', $ticket->fresh()->status);
    }

    public function test_f20_analytics_restricted_to_coordinator_and_excludes_planned_sprints(): void
    {
        $student = User::findOrFail(1);
        $supervisor = User::findOrFail(5);
        $coordinator = User::where('role', 'coordinator')->firstOrFail();

        // Student and Supervisor are blocked from Coordinator Analytics dashboard
        $this->actingAs($student)->get('/analytics')->assertSessionHas('error');
        $this->actingAs($supervisor)->get('/analytics')->assertSessionHas('error');

        $expectedActiveAvg = round((float) Sprint::whereIn('status', ['in_progress', 'submitted', 'reviewed'])->avg('attainment_percentage'), 1);

        // Create an unstarted ('planned') sprint with 0% attainment and verify it does not drag down active sprint attainment
        Sprint::create([
            'team_id' => 1,
            'sprint_number' => 4,
            'sprint_goal' => 'Future Planned Sprint',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-07',
            'status' => 'planned',
            'attainment_percentage' => 0,
        ]);

        // Coordinator accesses /analytics successfully and sees Sprint Velocity metric and unchanged active attainment average
        $this->actingAs($coordinator)
            ->get('/analytics')
            ->assertStatus(200)
            ->assertSee('Sprint Velocity')
            ->assertSee($expectedActiveAvg . '%');
    }
}
