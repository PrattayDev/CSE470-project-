<?php

namespace Tests\Feature;

use App\Models\ClearanceCertificate;
use App\Models\ConsultationSlot;
use App\Models\DefenseSchedule;
use App\Models\DocumentSubmission;
use App\Models\EquipmentRequisition;
use App\Models\LabEquipment;
use App\Models\MeetingMinute;
use App\Models\Sprint;
use App\Models\SprintTask;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\GradingCalculationService;
use App\Services\PenaltyCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SamaraAndSiddiqueWorkflowsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_f5_and_f6_sprint_bounds_and_task_status_recalculates_attainment(): void
    {
        $student1 = User::findOrFail(1);
        $student2 = User::findOrFail(2);

        // Sprint number > 4 rejected
        $this->actingAs($student1)
            ->post('/sprints', [
                'team_id' => 1,
                'sprint_number' => 5,
                'sprint_goal' => 'Invalid Sprint 5',
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-16',
            ])
            ->assertSessionHasErrors('sprint_number');

        // Overlapping sprint dates rejected (Sprint 0 is 2026-10-18 to 2026-10-24)
        $this->actingAs($student1)
            ->post('/sprints', [
                'team_id' => 1,
                'sprint_number' => 4,
                'sprint_goal' => 'Overlapping Sprint 4',
                'start_date' => '2026-10-20',
                'end_date' => '2026-10-23',
            ])
            ->assertSessionHas('error');

        // Valid Sprint 4 created with non-overlapping dates
        $this->actingAs($student1)
            ->post('/sprints', [
                'team_id' => 1,
                'sprint_number' => 4,
                'sprint_goal' => 'Final Sprint 4 Integration',
                'start_date' => '2026-12-01',
                'end_date' => '2026-12-07',
            ])
            ->assertSessionHas('success');

        $sprint = Sprint::where('team_id', 1)->where('sprint_number', 4)->firstOrFail();

        $this->actingAs($student1)->post('/sprints/tasks', [
            'sprint_id' => $sprint->id,
            'assigned_to' => $student1->id,
            'feature_number' => 5,
            'feature_title' => 'Sprint Planning',
            'task_description' => 'Task A',
            'status' => 'completed',
        ]);
        $this->actingAs($student1)->post('/sprints/tasks', [
            'sprint_id' => $sprint->id,
            'assigned_to' => $student2->id,
            'feature_number' => 6,
            'feature_title' => 'Sprint Review',
            'task_description' => 'Task B',
            'status' => 'todo',
        ]);

        $this->assertSame(50, (int) $sprint->fresh()->attainment_percentage);

        $taskB = SprintTask::where('sprint_id', $sprint->id)->where('feature_number', 6)->firstOrFail();

        // F6 Access Control: Teammate (student1) cannot update a task assigned to student2
        $this->actingAs($student1)
            ->post("/sprints/tasks/{$taskB->id}/status", [
                'status' => 'completed',
                'git_commit_hash' => 'deadbeef',
            ])
            ->assertSessionHas('error');

        $this->assertSame('todo', $taskB->fresh()->status);
        $this->assertSame(50, (int) $sprint->fresh()->attainment_percentage);

        // Assigned student (student2) updates their own task -> recalculates attainment to 100%
        $this->actingAs($student2)
            ->post("/sprints/tasks/{$taskB->id}/status", [
                'status' => 'completed',
                'git_commit_hash' => '99aabb1',
            ])
            ->assertSessionHas('success');

        $this->assertSame(100, (int) $sprint->fresh()->attainment_percentage);
    }

    public function test_f7_and_f8_consultation_slot_booking_and_individual_action_item_tracking(): void
    {
        $supervisor = User::findOrFail(5);
        $student = User::findOrFail(1);

        $this->actingAs($supervisor)->post('/consultations/slots', [
            'slot_date' => '2026-12-05',
            'start_time' => '10:00',
            'end_time' => '10:30',
            'room_or_meet_link' => 'UB40501',
        ])->assertSessionHas('success');

        $slot = ConsultationSlot::where('slot_date', '2026-12-05')->firstOrFail();

        $this->actingAs($student)->post("/consultations/slots/{$slot->id}/book", [
            'booked_by_team_id' => 1,
            'agenda' => 'First Agenda',
        ])->assertSessionHas('success');

        // Second booking rejected
        $this->actingAs($student)->post("/consultations/slots/{$slot->id}/book", [
            'booked_by_team_id' => 1,
            'agenda' => 'Second Agenda',
        ])->assertSessionHas('error');

        $this->actingAs($student)->post('/consultations/minutes', [
            'slot_id' => $slot->id,
            'team_id' => 1,
            'discussion_summary' => 'Minutes summary',
            'action_items' => "Item One; Item Two",
            'action_status' => 'pending',
        ])->assertSessionHas('success');

        $minutes = MeetingMinute::where('slot_id', $slot->id)->orderBy('id')->get();
        $this->assertCount(2, $minutes);

        $this->actingAs($student)->post("/consultations/minutes/{$minutes[0]->id}/status", [
            'action_status' => 'resolved',
        ])->assertSessionHas('success');

        $this->assertSame('resolved', $minutes[0]->fresh()->action_status);
        $this->assertSame('pending', $minutes[1]->fresh()->action_status);
    }

    public function test_f10_f11_f12_f13_f14_and_f19_guards_and_workflows(): void
    {
        $student = User::findOrFail(1);
        $supervisor = User::findOrFail(5);
        $labAdmin = User::findOrFail(6);

        // F10: Student blocked from rubric grading by RoleMiddleware
        $this->actingAs($student)->post('/evaluations/rubric', [
            'team_id' => 1,
            'student_id' => $student->id,
            'srs_and_sprint_marks' => 2,
            'mvc_implementation_marks' => 3,
            'individual_viva_marks' => 10,
        ])->assertSessionHas('error');

        // F11: Unreviewed student defaults to 0.0%
        $gradingService = app(GradingCalculationService::class);
        $weight = $gradingService->calculatePeerWeightageForStudent(1, 999999);
        $this->assertSame(0.0, $weight['contribution_percentage']);

        // F12 & F13: Lab equipment loan approval & single-return protection
        $this->actingAs($labAdmin)->post('/inventory/equipment', [
            'asset_code' => 'BRACU-UNIT-77',
            'equipment_name' => 'FPGA Dev Board',
            'category' => 'Hardware',
            'total_quantity' => 2,
            'daily_late_fine_bdt' => 50,
            'condition_status' => 'excellent',
        ])->assertSessionHas('success');

        $eq = LabEquipment::where('asset_code', 'BRACU-UNIT-77')->firstOrFail();

        $this->actingAs($student)->post('/inventory/requisition', [
            'team_id' => 1,
            'equipment_id' => $eq->id,
            'quantity' => 1,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-05',
        ])->assertSessionHas('success');

        $req = EquipmentRequisition::where('equipment_id', $eq->id)->firstOrFail();
        $this->assertSame('requested', $req->status);
        $this->assertSame(2, (int) $eq->fresh()->available_quantity);

        $this->actingAs($labAdmin)->post("/inventory/requisition/{$req->id}/approve")->assertSessionHas('success');
        $this->assertSame('issued', $req->fresh()->status);
        $this->assertSame(1, (int) $eq->fresh()->available_quantity);

        $this->actingAs($labAdmin)->post("/inventory/requisition/{$req->id}/return", [
            'returned_date' => '2026-11-07',
        ])->assertSessionHas('success');

        $this->assertSame(2, (int) $eq->fresh()->available_quantity);

        // Returning a second time does not increment stock above 2
        $this->actingAs($labAdmin)->post("/inventory/requisition/{$req->id}/return", [
            'returned_date' => '2026-11-07',
        ])->assertSessionHas('error');
        $this->assertSame(2, (int) $eq->fresh()->available_quantity);

        // F19: Printable Clearance Certificate is restricted to team members, team supervisor, lab_admin, and coordinator
        $cert = ClearanceCertificate::create([
            'team_id' => 1,
            'certificate_serial' => 'BRACU-CSE470-2026-GRP1-TEST1',
            'lab_dues_cleared' => true,
            'final_code_submitted' => true,
            'srs_approved' => true,
            'issued_by' => $labAdmin->id,
            'issued_at' => now(),
        ]);

        $outsiderStudent = User::create([
            'student_or_faculty_id' => '23993001',
            'name' => 'Outsider Clearance Student',
            'email' => 'outsider.cert@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);
        $otherTeam = Team::create([
            'group_name' => 'Other Clearance Team',
            'section' => '09',
            'leader_id' => $outsiderStudent->id,
            'status' => 'locked',
        ]);
        TeamMember::create(['team_id' => $otherTeam->id, 'student_id' => $outsiderStudent->id]);

        // Outsider student gets 403 when trying to view Team 1's printable certificate
        $this->actingAs($outsiderStudent)
            ->get("/clearance/{$cert->id}/print")
            ->assertStatus(403);

        // Team 1 member and Lab Admin can view Team 1's printable certificate
        $this->actingAs($student)
            ->get("/clearance/{$cert->id}/print")
            ->assertStatus(200)
            ->assertSee('BRACU-CSE470-2026-GRP1-TEST1');

        $this->actingAs($labAdmin)
            ->get("/clearance/{$cert->id}/print")
            ->assertStatus(200);
    }
}
