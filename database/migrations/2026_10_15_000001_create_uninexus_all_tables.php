<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Users (Auth + Feature 1: Multi-Role Academic Profile & Skill Matrix)
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

        // 2. Teams & Members (Feature 2: Smart Team Formation & Teammate Finder)
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

        // 3. Proposals (Feature 3: Topic Proposal Bidding & Feature 4: Duplicate Topic Similarity Checker)
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

        // 4. Sprints & Tasks (Feature 5: Weekly Sprint Planning & Feature 6: Attainment Review)
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->integer('sprint_number');
            $table->string('sprint_goal', 255);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('attainment_summary')->nullable();
            $table->text('blockers_reported')->nullable();
            $table->string('github_pr_or_commit_url', 255)->nullable();
            $table->string('demo_video_url', 255)->nullable();
            $table->enum('status', ['planned', 'in_progress', 'submitted', 'reviewed'])->default('planned');
            $table->text('supervisor_remarks')->nullable();
            $table->integer('attainment_percentage')->default(0);
            $table->timestamps();
        });

        Schema::create('sprint_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->integer('feature_number');
            $table->string('feature_title', 200);
            $table->text('task_description');
            $table->string('git_commit_hash', 80)->nullable();
            $table->enum('status', ['todo', 'in_progress', 'completed', 'carried_over'])->default('todo');
            $table->timestamps();
        });

        // 5. Consultations & Minutes (Feature 7: Slot Booking & Feature 8: Meeting Minutes Tracker)
        Schema::create('consultation_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booked_by_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room_or_meet_link', 200);
            $table->string('agenda', 255)->nullable();
            $table->enum('status', ['available', 'booked', 'completed', 'cancelled'])->default('available');
            $table->timestamps();
        });

        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('consultation_slots')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->text('discussion_summary');
            $table->text('action_items');
            $table->enum('action_status', ['pending', 'in_progress', 'resolved'])->default('pending');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // 6. Document Submissions (Feature 9: Document Version Control & SRS Portal)
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

        // 7. Rubric & Peer Evaluations (Feature 10 & Feature 11)
        Schema::create('rubric_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evaluated_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('srs_and_sprint_marks', 4, 2)->default(0.00);
            $table->decimal('mvc_implementation_marks', 4, 2)->default(0.00);
            $table->decimal('individual_viva_marks', 4, 2)->default(0.00);
            $table->decimal('total_marks', 5, 2)->default(0.00);
            $table->text('viva_remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('peer_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('evaluator_student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evaluatee_student_id')->constrained('users')->cascadeOnDelete();
            $table->integer('coding_contribution_score');
            $table->integer('sprint_reliability_score');
            $table->integer('documentation_score');
            $table->text('justification');
            $table->timestamps();
        });

        // 8. Lab Inventory & Requisitions (Feature 12 & Feature 13)
        Schema::create('lab_equipments', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 50)->unique();
            $table->string('equipment_name', 150);
            $table->string('category', 80);
            $table->integer('total_quantity')->default(1);
            $table->integer('available_quantity')->default(1);
            $table->decimal('daily_late_fine_bdt', 8, 2)->default(50.00);
            $table->enum('condition_status', ['excellent', 'good', 'maintenance', 'damaged'])->default('excellent');
            $table->timestamps();
        });

        Schema::create('equipment_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('lab_equipments')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('returned_date')->nullable();
            $table->decimal('calculated_fine_bdt', 8, 2)->default(0.00);
            $table->enum('status', ['requested', 'approved', 'issued', 'returned', 'overdue'])->default('requested');
            $table->timestamps();
        });

        // 9. Defense Schedule (Feature 14)
        Schema::create('defense_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->date('defense_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room_number', 60);
            $table->foreignId('panel_chair_id')->constrained('users')->cascadeOnDelete();
            $table->string('external_examiner_name', 120);
            $table->enum('status', ['scheduled', 'completed', 'rescheduled'])->default('scheduled');
            $table->timestamps();
        });

        // 10. Notifications, Archive, AI-Compliance, Disputes & Clearance (Features 15, 16, 17, 18, 19)
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('message');
            $table->enum('type', ['deadline', 'approval', 'penalty', 'viva', 'general'])->default('general');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('project_archives', function (Blueprint $table) {
            $table->id();
            $table->string('project_title', 255);
            $table->string('semester', 40);
            $table->string('team_members_summary', 255);
            $table->string('tech_stack', 120);
            $table->text('abstract');
            $table->string('github_repo_url', 255)->nullable();
            $table->string('keywords', 255);
            $table->boolean('featured_award')->default(false);
            $table->timestamps();
        });

        Schema::create('compliance_disclosures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('ai_tools_used', 255);
            $table->text('ai_usage_purpose');
            $table->text('influenced_modules');
            $table->text('third_party_packages');
            $table->boolean('student_ownership_verified')->default(true);
            $table->boolean('supervisor_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('scope_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->enum('request_type', ['feature_change', 'member_dispute', 'stack_adjustment']);
            $table->text('current_scope_detail');
            $table->text('proposed_change_detail');
            $table->text('reason_justification');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('coordinator_decision_note')->nullable();
            $table->timestamps();
        });

        Schema::create('clearance_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('certificate_serial', 60)->unique();
            $table->boolean('lab_dues_cleared')->default(true);
            $table->boolean('final_code_submitted')->default(true);
            $table->boolean('srs_approved')->default(true);
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('issued_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_certificates');
        Schema::dropIfExists('scope_change_requests');
        Schema::dropIfExists('compliance_disclosures');
        Schema::dropIfExists('project_archives');
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('defense_schedules');
        Schema::dropIfExists('equipment_requisitions');
        Schema::dropIfExists('lab_equipments');
        Schema::dropIfExists('peer_evaluations');
        Schema::dropIfExists('rubric_evaluations');
        Schema::dropIfExists('document_submissions');
        Schema::dropIfExists('meeting_minutes');
        Schema::dropIfExists('consultation_slots');
        Schema::dropIfExists('sprint_tasks');
        Schema::dropIfExists('sprints');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('users');
    }
};
