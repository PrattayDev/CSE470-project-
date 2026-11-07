-- ============================================================================
-- UniNexus: Smart University Thesis, Project & Lab Resource Management System
-- Course: CSE 470 (Software Engineering) - MVC Architecture
-- Database: MySQL 8.0+ / MariaDB 10.4+
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `uninexus_cse470` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `uninexus_cse470`;

-- ----------------------------------------------------------------------------
-- 1. USERS TABLE (Supports Auth + Feature 1: Multi-Role Academic Profile & Skill Matrix)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_or_faculty_id` VARCHAR(30) UNIQUE NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'supervisor', 'lab_admin', 'coordinator') NOT NULL DEFAULT 'student',
    `department` VARCHAR(80) DEFAULT 'CSE',
    `section` VARCHAR(20) NULL,
    `cgpa` DECIMAL(3,2) NULL,
    `skills` JSON NULL COMMENT 'Array of technical skills e.g. ["Laravel", "MySQL", "MVC", "AI"]',
    `research_interests` TEXT NULL,
    `max_group_quota` INT DEFAULT 5 COMMENT 'Used when role = supervisor',
    `github_username` VARCHAR(100) NULL,
    `phone` VARCHAR(30) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- 2. TEAMS & TEAM MEMBERS (Feature 2: Smart Team Formation & Teammate Finder)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teams` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_name` VARCHAR(100) NOT NULL,
    `section` VARCHAR(20) NOT NULL,
    `leader_id` BIGINT UNSIGNED NOT NULL,
    `supervisor_id` BIGINT UNSIGNED NULL,
    `status` ENUM('forming', 'locked', 'supervised', 'cleared') DEFAULT 'forming',
    `preferred_stack` VARCHAR(120) DEFAULT 'Laravel MVC + MySQL',
    `domain_interest` VARCHAR(150) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`leader_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `team_members` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `assigned_module` VARCHAR(150) NULL,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_team_student` (`team_id`, `student_id`),
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `team_invitations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `invited_student_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    `message` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`invited_student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 3. PROPOSALS (Feature 3: Topic Proposal Bidding & Feature 4: Duplicate Similarity Checker)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proposals` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `supervisor_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `problem_statement` TEXT NOT NULL,
    `proposed_features` JSON NOT NULL COMMENT 'List of 20 substantive features',
    `tech_stack` VARCHAR(150) NOT NULL DEFAULT 'Laravel (PHP), Blade, MySQL',
    `similarity_score` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Calculated by TopicSimilarityService',
    `matched_archive_title` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'revision_required', 'rejected') DEFAULT 'pending',
    `supervisor_feedback` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 4. SPRINTS & TASKS (Feature 5: Sprint Planning & Feature 6: Sprint Attainment Review)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sprints` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `sprint_number` INT NOT NULL COMMENT 'Sprint 0, 1, 2, 3, 4',
    `sprint_goal` VARCHAR(255) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `attainment_summary` TEXT NULL,
    `blockers_reported` TEXT NULL,
    `github_pr_or_commit_url` VARCHAR(255) NULL,
    `demo_video_url` VARCHAR(255) NULL,
    `status` ENUM('planned', 'in_progress', 'submitted', 'reviewed') DEFAULT 'planned',
    `supervisor_remarks` TEXT NULL,
    `attainment_percentage` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `sprint_tasks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sprint_id` BIGINT UNSIGNED NOT NULL,
    `assigned_to` BIGINT UNSIGNED NOT NULL,
    `feature_number` INT NOT NULL,
    `feature_title` VARCHAR(200) NOT NULL,
    `task_description` TEXT NOT NULL,
    `git_commit_hash` VARCHAR(80) NULL,
    `status` ENUM('todo', 'in_progress', 'completed', 'carried_over') DEFAULT 'todo',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`sprint_id`) REFERENCES `sprints`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 5. CONSULTATION SLOTS & MINUTES (Feature 7: Slot Booking & Feature 8: Meeting Minutes)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation_slots` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `supervisor_id` BIGINT UNSIGNED NOT NULL,
    `booked_by_team_id` BIGINT UNSIGNED NULL,
    `slot_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room_or_meet_link` VARCHAR(200) NOT NULL,
    `agenda` VARCHAR(255) NULL,
    `status` ENUM('available', 'booked', 'completed', 'cancelled') DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`booked_by_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `meeting_minutes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slot_id` BIGINT UNSIGNED NOT NULL,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `discussion_summary` TEXT NOT NULL,
    `action_items` TEXT NOT NULL,
    `action_status` ENUM('pending', 'in_progress', 'resolved') DEFAULT 'pending',
    `assigned_to` BIGINT UNSIGNED NULL,
    `due_date` DATE NULL,
    `recorded_by` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`slot_id`) REFERENCES `consultation_slots`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 6. DOCUMENT VERSION CONTROL (Feature 9: SRS/Report Submission & Versioning)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_submissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `submitted_by` BIGINT UNSIGNED NOT NULL,
    `document_type` ENUM('SRS', 'UML_Class_Diagram', 'Sprint_Report', 'Final_Report') NOT NULL,
    `version_tag` VARCHAR(20) NOT NULL DEFAULT 'v1.0',
    `file_path_or_drive_url` VARCHAR(255) NOT NULL,
    `changelog_notes` TEXT NOT NULL,
    `faculty_review_status` ENUM('pending', 'approved', 'needs_update') DEFAULT 'pending',
    `faculty_comments` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 7. RUBRIC & PEER EVALUATIONS (Feature 10: Rubric Viva Grading & Feature 11: Peer Weightage)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rubric_evaluations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `evaluated_by` BIGINT UNSIGNED NOT NULL,
    `srs_and_sprint_marks` DECIMAL(4,2) DEFAULT 0.00 COMMENT 'Out of 2.00',
    `mvc_implementation_marks` DECIMAL(4,2) DEFAULT 0.00 COMMENT 'Out of 3.00',
    `individual_viva_marks` DECIMAL(4,2) DEFAULT 0.00 COMMENT 'Out of 10.00',
    `total_marks` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Out of 15.00',
    `viva_remarks` TEXT NULL,
    `is_finalized` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`evaluated_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `peer_evaluations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `evaluator_student_id` BIGINT UNSIGNED NOT NULL,
    `evaluatee_student_id` BIGINT UNSIGNED NOT NULL,
    `coding_contribution_score` INT NOT NULL COMMENT '1 to 10',
    `sprint_reliability_score` INT NOT NULL COMMENT '1 to 10',
    `documentation_score` INT NOT NULL COMMENT '1 to 10',
    `justification` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`evaluator_student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`evaluatee_student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 8. LAB INVENTORY & REQUISITIONS (Feature 12: Inventory & Feature 13: Requisition & Penalty)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lab_equipments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `asset_code` VARCHAR(50) UNIQUE NOT NULL,
    `equipment_name` VARCHAR(150) NOT NULL,
    `category` VARCHAR(80) NOT NULL,
    `total_quantity` INT NOT NULL DEFAULT 1,
    `available_quantity` INT NOT NULL DEFAULT 1,
    `daily_late_fine_bdt` DECIMAL(8,2) NOT NULL DEFAULT 50.00,
    `condition_status` ENUM('excellent', 'good', 'maintenance', 'damaged') DEFAULT 'excellent',
    `condition_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `equipment_requisitions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `equipment_id` BIGINT UNSIGNED NOT NULL,
    `requested_by` BIGINT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `issue_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `returned_date` DATE NULL,
    `calculated_fine_bdt` DECIMAL(8,2) DEFAULT 0.00,
    `fine_paid_amount` DECIMAL(8,2) DEFAULT 0.00,
    `fine_paid_at` TIMESTAMP NULL,
    `status` ENUM('requested', 'approved', 'issued', 'returned', 'overdue') DEFAULT 'requested',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`equipment_id`) REFERENCES `lab_equipments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 9. DEFENSE SCHEDULER (Feature 14: Defense/Viva Room & Panel Scheduling Engine)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `defense_schedules` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `defense_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room_number` VARCHAR(60) NOT NULL,
    `panel_chair_id` BIGINT UNSIGNED NOT NULL,
    `external_examiner_name` VARCHAR(120) NOT NULL,
    `status` ENUM('scheduled', 'completed', 'rescheduled', 'cancelled') DEFAULT 'scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`panel_chair_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 10. NOTIFICATIONS, ARCHIVE, AI-COMPLIANCE, DISPUTES & CLEARANCE (Features 15, 16, 17, 18, 19)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('deadline', 'approval', 'penalty', 'viva', 'general') DEFAULT 'general',
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `project_archives` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_title` VARCHAR(255) NOT NULL,
    `semester` VARCHAR(40) NOT NULL,
    `team_members_summary` VARCHAR(255) NOT NULL,
    `tech_stack` VARCHAR(120) NOT NULL,
    `abstract` TEXT NOT NULL,
    `github_repo_url` VARCHAR(255) NULL,
    `keywords` VARCHAR(255) NOT NULL,
    `featured_award` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `compliance_disclosures` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `ai_tools_used` VARCHAR(255) NOT NULL,
    `ai_usage_purpose` TEXT NOT NULL,
    `influenced_modules` TEXT NOT NULL,
    `third_party_packages` TEXT NOT NULL,
    `student_ownership_verified` BOOLEAN DEFAULT TRUE,
    `supervisor_verified` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `scope_change_requests` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `requested_by` BIGINT UNSIGNED NOT NULL,
    `request_type` ENUM('feature_change', 'member_dispute', 'stack_adjustment') NOT NULL,
    `current_scope_detail` TEXT NOT NULL,
    `proposed_change_detail` TEXT NOT NULL,
    `reason_justification` TEXT NOT NULL,
    `status` ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
    `coordinator_decision_note` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `clearance_certificates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `certificate_serial` VARCHAR(60) UNIQUE NOT NULL,
    `lab_dues_cleared` BOOLEAN DEFAULT TRUE,
    `final_code_submitted` BOOLEAN DEFAULT TRUE,
    `srs_approved` BOOLEAN DEFAULT TRUE,
    `issued_by` BIGINT UNSIGNED NOT NULL,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`issued_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ============================================================================
-- SEED DATA (Section 9 Group 3 + Faculty + Demo Records)
-- ============================================================================
INSERT INTO `users` (`id`, `student_or_faculty_id`, `name`, `email`, `password`, `role`, `department`, `section`, `cgpa`, `skills`, `research_interests`, `max_group_quota`, `github_username`) VALUES
(1, '23101048', 'Prattay Dev Burman', 'prattay.dev.burman@g.bracu.ac.bd', '$2y$12$se5MgvsOwIQlONsExhIj8OH/7388a6dWSJ/YiIRVMuaAt/z3mkSRi', 'student', 'CSE', '09', 3.75, '["Laravel", "PHP", "MySQL", "Cybersecurity", "MVC"]', 'Software Engineering & Web Security', 0, 'prattay-dev'),
(2, '23101159', 'Rownak Jahan', 'rownak.jahan@g.bracu.ac.bd', '$2y$12$se5MgvsOwIQlONsExhIj8OH/7388a6dWSJ/YiIRVMuaAt/z3mkSRi', 'student', 'CSE', '09', 3.68, '["Laravel", "Blade UI", "Tailwind", "UML"]', 'Human-Computer Interaction & Agile', 0, 'rownak-jahan'),
(3, '23301214', 'Samara Shahjeen Huq', 'samara.shahjeen.huq@g.bracu.ac.bd', '$2y$12$se5MgvsOwIQlONsExhIj8OH/7388a6dWSJ/YiIRVMuaAt/z3mkSRi', 'student', 'CSE', '09', 3.72, '["MySQL", "PHP", "Analytics", "Testing"]', 'Data Analytics & Quality Assurance', 0, 'samara-huq'),
(4, '22301762', 'Md. Siddique Bishwas', 'siddique.bishwas@g.bracu.ac.bd', '$2y$12$se5MgvsOwIQlONsExhIj8OH/7388a6dWSJ/YiIRVMuaAt/z3mkSRi', 'student', 'CSE', '09', 3.60, '["Laravel", "Inventory Systems", "REST API"]', 'IoT & Resource Management', 0, 'siddique-bishwas'),
(5, 'FAC-CSE-01', 'Course Faculty (Supervisor)', 'faculty.cse470@bracu.ac.bd', '$2y$12$se5MgvsOwIQlONsExhIj8OH/7388a6dWSJ/YiIRVMuaAt/z3mkSRi', 'supervisor', 'CSE', '09', NULL, '["Software Architecture", "MVC", "Agile Scrum", "Formal Verification"]', 'Software Engineering, Design Patterns, AI Engineering', 6, 'faculty-cse470'),
(6, 'LAB-ADM-01', 'CSE Lab Administrator', 'labadmin@bracu.ac.bd', '$2y$12$se5MgvsOwIQlONsExhIj8OH/7388a6dWSJ/YiIRVMuaAt/z3mkSRi', 'lab_admin', 'CSE', NULL, NULL, '["Hardware Inventory", "Embedded Systems"]', 'Lab Operations', 0, 'cse-labadmin'),
(7, 'COORD-01', 'CSE 470 Course Coordinator', 'coordinator.cse470@bracu.ac.bd', '$2y$12$se5MgvsOwIQlONsExhIj8OH/7388a6dWSJ/YiIRVMuaAt/z3mkSRi', 'coordinator', 'CSE', NULL, NULL, '["Curriculum Design", "Software Quality Metrics"]', 'Software Engineering Governance', 8, 'cse470-coordinator');

INSERT INTO `teams` (`id`, `group_name`, `section`, `leader_id`, `supervisor_id`, `status`, `preferred_stack`, `domain_interest`) VALUES
(1, 'Group 3 - Section 9', '09', 1, 5, 'supervised', 'Laravel 10 MVC + Blade + MySQL', 'Academic Workflow & Lab Resource Automation');

INSERT INTO `team_members` (`team_id`, `student_id`, `assigned_module`) VALUES
(1, 1, 'Module 1 (F1, F2, F3, F4, F20): Academic Profile, Team Finder, Proposal Bidding, Similarity Engine & Cohort Analytics'),
(1, 2, 'Module 2 (F9, F15, F16, F17, F18): SRS Versioning, Deadline Broadcasts, Digital Archive, AI Compliance & Scope Disputes'),
(1, 3, 'Module 3 (F7, F8, F10, F11, F14): Consultation Booking, Meeting Minutes, Rubric Viva Grading, Peer Weightage & Defense Scheduler'),
(1, 4, 'Module 4 (F5, F6, F12, F13, F19): Weekly Sprint Planner, Attainment Review, Lab Hardware Inventory, Requisition Penalty & Clearance PDF');

INSERT INTO `project_archives` (`project_title`, `semester`, `team_members_summary`, `tech_stack`, `abstract`, `github_repo_url`, `keywords`, `featured_award`) VALUES
('WeHeal - Integrated Hospital Telemedicine Portal', 'Summer 2025', 'Group 2 (4 Members)', 'Laravel MVC + MySQL', 'A hospital appointment and prescription workflow system following MVC pattern.', 'https://github.com/example/weheal', 'healthcare telemedicine doctor appointment prescription', 1),
('AgroPulse - Smart Crop Supply Chain Tracker', 'Spring 2026', 'Group 5 (4 Members)', 'ASP.NET Core MVC + SQL Server', 'Tracks agricultural produce from farmers to wholesale distributors with bidding.', 'https://github.com/example/agropulse', 'agriculture supply chain inventory bidding', 0);

INSERT INTO `proposals` (`team_id`, `supervisor_id`, `title`, `problem_statement`, `proposed_features`, `tech_stack`, `similarity_score`, `matched_archive_title`, `status`, `supervisor_feedback`) VALUES
(1, 5, 'UniNexus — Smart University Thesis, Project & Lab Resource Management System', 'Managing 4-member student software engineering groups, weekly sprints, rubric grading, and lab hardware requisitions across multiple sections using spreadsheets leads to fragmented tracking and scheduling conflicts.', '["1. Multi-Role Academic Profile & Skill Matrix","2. Smart Team Formation & Teammate Finder","3. Supervisor Quota & Proposal Bidding","4. Duplicate Topic Similarity Checker","5. Weekly Sprint Planning Module","6. Sprint Attainment Review","7. Consultation Slot Booking","8. Meeting Minutes Tracker","9. Document Version Control Portal","10. Rubric Viva Grading System","11. Peer Evaluation Weightage Calculator","12. Lab Equipment Inventory","13. Lab Requisition & Return Penalty","14. Defense Room & Panel Scheduler","15. Automated Deadline Reminder","16. Project Showcase Archive","17. AI-Disclosure Compliance Tracker","18. Scope Change & Dispute Module","19. Automated Clearance Generator","20. Coordinator Analytics Dashboard"]', 'Laravel (PHP) MVC + Blade + MySQL', 8.50, 'WeHeal - Integrated Hospital Telemedicine Portal', 'approved', 'Excellent scope with 20 well-defined substantive workflows. Approved for Sprint development.');

INSERT INTO `sprints` (`id`, `team_id`, `sprint_number`, `sprint_goal`, `start_date`, `end_date`, `attainment_summary`, `blockers_reported`, `github_pr_or_commit_url`, `status`, `supervisor_remarks`, `attainment_percentage`) VALUES
(1, 1, 0, 'Framework setup, environment configuration, UML Class diagram, and SRS finalization.', '2026-10-18', '2026-10-24', 'Completed Laravel MVC setup, MySQL migrations, and initial SRS.', 'None', 'https://github.com/cse470-group3/uninexus/pull/1', 'reviewed', 'Great start. Proceed to Sprint 1 features.', 100),
(2, 1, 1, 'Implement Features 1-5: Skill Matrix, Team Finder, Proposal Bidding, Similarity Checker, and Sprint Planner.', '2026-10-25', '2026-11-08', 'All 5 features implemented and unit tested across MVC layers.', 'Minor CSS alignment fixed.', 'https://github.com/cse470-group3/uninexus/pull/5', 'in_progress', 'On track.', 85);

INSERT INTO `sprint_tasks` (`sprint_id`, `assigned_to`, `feature_number`, `feature_title`, `task_description`, `git_commit_hash`, `status`) VALUES
(2, 1, 1, 'Multi-Role Academic Profile & Skill Matrix', 'Create ProfileSkillController and skill matrix JSON tags.', 'a1b2c3d', 'completed'),
(2, 1, 4, 'Duplicate Topic & Title Similarity Checker', 'Implement TopicSimilarityService using Jaccard token matching.', 'e4f5g6h', 'completed'),
(2, 2, 9, 'Document Version Control & SRS Submission Portal', 'Build DocumentVersionController and version changelog views.', 'i7j8k9l', 'completed'),
(2, 3, 5, 'Milestone & Weekly Sprint Planning Module', 'Build SprintController and task distribution views.', 'm1n2o3p', 'completed'),
(2, 4, 12, 'Hardware & Lab Equipment Inventory Management', 'Create LabInventoryController with stock and fine rules.', 'q4r5s6t', 'completed');

INSERT INTO `lab_equipments` (`id`, `asset_code`, `equipment_name`, `category`, `total_quantity`, `available_quantity`, `daily_late_fine_bdt`, `condition_status`) VALUES
(1, 'BRACU-IOT-01', 'Raspberry Pi 5 (8GB Kit)', 'Embedded Systems', 10, 8, 50.00, 'excellent'),
(2, 'BRACU-GPU-02', 'NVIDIA Jetson Nano Developer Kit', 'AI / Edge Computing', 5, 4, 100.00, 'excellent'),
(3, 'BRACU-SEN-03', 'Arduino Mega Sensor Bundle', 'Robotics / IoT', 15, 14, 30.00, 'good');

INSERT INTO `equipment_requisitions` (`team_id`, `equipment_id`, `requested_by`, `quantity`, `issue_date`, `due_date`, `returned_date`, `calculated_fine_bdt`, `status`) VALUES
(1, 1, 4, 1, '2026-10-15', '2026-10-29', NULL, 0.00, 'issued');
