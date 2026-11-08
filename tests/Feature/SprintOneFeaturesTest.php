<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SprintOneFeaturesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_protected_sprint_one_routes(): void
    {
        $this->get('/profiles')->assertRedirect('/login');
        $this->post('/profiles/update', [])->assertRedirect('/login');
        $this->get('/documents')->assertRedirect('/login');
        $this->post('/documents', [])->assertRedirect('/login');
    }

    public function test_f1_student_can_update_profile_but_cannot_modify_supervision_quota(): void
    {
        $student = User::where('role', 'student')->firstOrFail();

        $this->actingAs($student)
            ->post('/profiles/update', [
                'name' => $student->name,
                'section' => '09',
                'cgpa' => 3.78,
                'skills_csv' => 'Laravel, PHP, MySQL, MVC',
                'research_interests' => 'Software Engineering',
                'github_username' => 'prattay-dev',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($student)
            ->post('/profiles/update', [
                'name' => $student->name,
                'skills_csv' => 'Laravel, PHP',
                'max_group_quota' => 10,
            ])
            ->assertSessionHasErrors('max_group_quota');
    }

    public function test_f1_supervisor_can_update_supervision_quota(): void
    {
        $supervisor = User::where('role', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->post('/profiles/update', [
                'name' => $supervisor->name,
                'section' => '09',
                'skills_csv' => 'Software Architecture, MVC, UML',
                'max_group_quota' => 6,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_f9_enforces_team_ownership_sequential_versions_private_storage_and_supervisor_review(): void
    {
        $student = User::findOrFail(2); // Rownak
        $supervisor = User::where('role', 'supervisor')->firstOrFail();

        // Create an isolated team inside the rolled-back transaction (never deleting seeded records)
        $team = Team::create([
            'group_name' => 'Temp Test Group F9',
            'section' => '09',
            'leader_id' => $student->id,
            'supervisor_id' => $supervisor->id,
            'status' => 'supervised',
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'student_id' => $student->id,
            'assigned_module' => 'Module 2',
        ]);

        // 1. Valid v1.0 file upload stored in private storage (not public/)
        $file = UploadedFile::fake()->create('sprint1_report.pdf', 120, 'application/pdf');
        $this->actingAs($student)
            ->post('/documents', [
                'team_id' => $team->id,
                'document_type' => 'Sprint_Report',
                'version_tag' => 'v1.0',
                'document_file' => $file,
                'changelog_notes' => 'Sprint 1 report v1.0',
            ])
            ->assertSessionHasNoErrors();

        $doc = DocumentSubmission::where('team_id', $team->id)->where('document_type', 'Sprint_Report')->firstOrFail();
        $this->assertStringStartsWith('private/documents/', $doc->file_path_or_drive_url);

        // 2. Duplicate v1.0 is rejected
        $this->actingAs($student)
            ->post('/documents', [
                'team_id' => $team->id,
                'document_type' => 'Sprint_Report',
                'version_tag' => 'v1.0',
                'file_path_or_drive_url' => 'https://docs.google.com/document/d/sprint1-v1',
                'changelog_notes' => 'Duplicate version attempt',
            ])
            ->assertSessionHasErrors('version_tag');

        // 3. Another student outside the team cannot see or download the private document
        $outsider = User::create([
            'student_or_faculty_id' => '99990001',
            'name' => 'Outsider Student',
            'email' => 'outsider.f9@g.bracu.ac.bd',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'department' => 'CSE',
            'section' => '09',
        ]);

        $this->actingAs($outsider)
            ->get("/documents/{$doc->id}/download")
            ->assertStatus(403);

        // 4. Team member and assigned supervisor can download the private document
        $this->actingAs($student)
            ->get("/documents/{$doc->id}/download")
            ->assertStatus(200);

        // 5. Supervisor can review and approve document
        $this->actingAs($supervisor)
            ->post("/documents/{$doc->id}/review", [
                'faculty_review_status' => 'approved',
                'faculty_comments' => 'Verified Sprint 1 report.',
            ])
            ->assertSessionHas('success');

        // Clean up temporary file created in private storage during test
        $fullPath = storage_path('app/' . $doc->file_path_or_drive_url);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
