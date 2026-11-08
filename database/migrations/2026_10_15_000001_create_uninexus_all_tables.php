<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Users (Auth + Feature 1: Multi-Role Academic Profile & Skill Matrix - Prattay Sprint 1)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('student_or_faculty_id', 30)->unique();
            $table->string('name', 120);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->enum('role', ['student', 'supervisor', 'lab_admin', 'coordinator'])->default('student');
            $table->string('department', 80)->default('CSE');
            $table->string('section', 20)->nullable();
            $table->decimal('cgpa', 3, 2)->nullable();
            $table->json('skills')->nullable();
            $table->text('research_interests')->nullable();
            $table->integer('max_group_quota')->default(5);
            $table->string('github_username', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });

        // 2. Teams & Members (Feature 2: Smart Team Formation & Teammate Finder - Prattay Sprint 1)
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('group_name', 100);
            $table->string('section', 20);
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['forming', 'locked', 'supervised', 'cleared'])->default('forming');
            $table->string('preferred_stack', 120)->default('Laravel MVC + MySQL');
            $table->string('domain_interest', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('assigned_module', 150)->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->unique(['team_id', 'student_id']);
        });

        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('invited_student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->string('message', 255)->nullable();
            $table->timestamps();
        });

        // 3. Proposals (Supporting Supervisor Quota & Team Supervision linkage)
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('problem_statement');
            $table->json('proposed_features');
            $table->string('tech_stack', 150)->default('Laravel (PHP), Blade, MySQL');
            $table->decimal('similarity_score', 5, 2)->default(0.00);
            $table->string('matched_archive_title', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'revision_required', 'rejected'])->default('pending');
            $table->text('supervisor_feedback')->nullable();
            $table->timestamps();
        });

        // 4. Document Submissions (Feature 9: Document Version Control & SRS Portal - Rownak Sprint 1)
        Schema::create('document_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->enum('document_type', ['SRS', 'UML_Class_Diagram', 'Sprint_Report', 'Final_Report']);
            $table->string('version_tag', 20)->default('v1.0');
            $table->string('file_path_or_drive_url', 255);
            $table->text('changelog_notes');
            $table->enum('faculty_review_status', ['pending', 'approved', 'needs_update'])->default('pending');
            $table->text('faculty_comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_submissions');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('users');
    }
};
