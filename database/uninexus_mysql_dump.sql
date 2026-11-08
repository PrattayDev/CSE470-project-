-- ============================================================================
-- UniNexus: Smart University Thesis, Project & Lab Resource Management System
-- Course: CSE 470 (Software Engineering) - MVC Architecture (Sprint 1 Schema)
-- Database: MySQL 8.0+ / MariaDB 10.4+
-- Sprint 1 Scope:
--   • Mandatory Auth (Login, Logout, Registration)
--   • Feature 1 (Prattay): Multi-Role Academic Profile & Skill Matrix
--   • Feature 2 (Prattay): Smart Team Formation & Teammate Finder
--   • Feature 9 (Rownak): Document Version Control & SRS/Report Submission Portal
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
-- 3. PROPOSALS TABLE (Supports Supervisor Quota & Team Supervision Linkage)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proposals` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `supervisor_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `problem_statement` TEXT NOT NULL,
    `proposed_features` JSON NOT NULL,
    `tech_stack` VARCHAR(150) NOT NULL DEFAULT 'Laravel (PHP), Blade, MySQL',
    `similarity_score` DECIMAL(5,2) DEFAULT 0.00,
    `matched_archive_title` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'revision_required', 'rejected') DEFAULT 'pending',
    `supervisor_feedback` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 4. DOCUMENT VERSION CONTROL (Feature 9: SRS/Report Submission & Versioning)
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

-- ============================================================================
-- SEED DATA (Section 9 Group 3 + Faculty + Sprint 1 Demo Records)
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

INSERT INTO `document_submissions` (`id`, `team_id`, `submitted_by`, `document_type`, `version_tag`, `file_path_or_drive_url`, `changelog_notes`, `faculty_review_status`, `faculty_comments`) VALUES
(1, 1, 2, 'SRS', 'v1.0', 'https://docs.google.com/document/d/uninexus-srs-v1', 'Initial SRS & 20 Substantive Features Proposal for Sprint 1.', 'approved', 'Approved for Sprint 1 implementation.');
