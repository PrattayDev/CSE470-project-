<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        // Seed Section 9 Group 3 Members + Supervisor + Lab Admin + Coordinator
        DB::table('users')->insert([
            [
                'id' => 1,
                'student_or_faculty_id' => '23101048',
                'name' => 'Prattay Dev Burman',
                'email' => 'prattay.dev.burman@g.bracu.ac.bd',
                'password' => $password,
                'role' => 'student',
                'department' => 'CSE',
                'section' => '09',
                'cgpa' => 3.75,
                'skills' => json_encode(['Laravel', 'PHP', 'MySQL', 'Cybersecurity', 'MVC']),
                'research_interests' => 'Software Engineering & Web Security',
                'max_group_quota' => 0,
                'github_username' => 'prattay-dev',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'student_or_faculty_id' => '23101159',
                'name' => 'Rownak Jahan',
                'email' => 'rownak.jahan@g.bracu.ac.bd',
                'password' => $password,
                'role' => 'student',
                'department' => 'CSE',
                'section' => '09',
                'cgpa' => 3.68,
                'skills' => json_encode(['Laravel', 'Blade UI', 'Tailwind', 'UML']),
                'research_interests' => 'Human-Computer Interaction & Agile Workflows',
                'max_group_quota' => 0,
                'github_username' => 'rownak-jahan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'student_or_faculty_id' => '23301214',
                'name' => 'Samara Shahjeen Huq',
                'email' => 'samara.shahjeen.huq@g.bracu.ac.bd',
                'password' => $password,
                'role' => 'student',
                'department' => 'CSE',
                'section' => '09',
                'cgpa' => 3.72,
                'skills' => json_encode(['MySQL', 'PHP', 'Analytics', 'Testing']),
                'research_interests' => 'Data Analytics & Software Quality Metrics',
                'max_group_quota' => 0,
                'github_username' => 'samara-huq',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'student_or_faculty_id' => '22301762',
                'name' => 'Md. Siddique Bishwas',
                'email' => 'siddique.bishwas@g.bracu.ac.bd',
                'password' => $password,
                'role' => 'student',
                'department' => 'CSE',
                'section' => '09',
                'cgpa' => 3.60,
                'skills' => json_encode(['Laravel', 'Inventory Systems', 'REST API']),
                'research_interests' => 'IoT & Lab Resource Automation',
                'max_group_quota' => 0,
                'github_username' => 'siddique-bishwas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'student_or_faculty_id' => 'FAC-CSE-01',
                'name' => 'Section 09 Faculty (Supervisor)',
                'email' => 'faculty.cse470@bracu.ac.bd',
                'password' => $password,
                'role' => 'supervisor',
                'department' => 'CSE',
                'section' => '09',
                'cgpa' => null,
                'skills' => json_encode(['Software Architecture', 'MVC', 'Agile Scrum', 'UML']),
                'research_interests' => 'Software Engineering, Design Patterns, AI Engineering',
                'max_group_quota' => 6,
                'github_username' => 'faculty-cse470',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'student_or_faculty_id' => 'LAB-ADM-01',
                'name' => 'CSE Lab Administrator',
                'email' => 'labadmin@bracu.ac.bd',
                'password' => $password,
                'role' => 'lab_admin',
                'department' => 'CSE',
                'section' => null,
                'cgpa' => null,
                'skills' => json_encode(['Hardware Inventory', 'Embedded Systems']),
                'research_interests' => 'Lab Operations',
                'max_group_quota' => 0,
                'github_username' => 'cse-labadmin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'student_or_faculty_id' => 'COORD-01',
                'name' => 'CSE 470 Course Coordinator',
                'email' => 'coordinator.cse470@bracu.ac.bd',
                'password' => $password,
                'role' => 'coordinator',
                'department' => 'CSE',
                'section' => null,
                'cgpa' => null,
                'skills' => json_encode(['Curriculum Design', 'Software Quality Metrics']),
                'research_interests' => 'Software Engineering Governance',
                'max_group_quota' => 8,
                'github_username' => 'cse470-coordinator',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Seed Team (Group 3 - Section 9)
        DB::table('teams')->insert([
            'id' => 1,
            'group_name' => 'Group 3 - Section 9',
            'section' => '09',
            'leader_id' => 1,
            'supervisor_id' => 5,
            'status' => 'supervised',
            'preferred_stack' => 'Laravel 10 MVC + Blade + MySQL',
            'domain_interest' => 'Academic Workflow & Lab Resource Automation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_members')->insert([
            ['team_id' => 1, 'student_id' => 1, 'assigned_module' => 'Module 1 (F1, F2, F3, F4, F20): Profile, Team Finder, Bidding, Similarity & Analytics'],
            ['team_id' => 1, 'student_id' => 2, 'assigned_module' => 'Module 2 (F9, F15, F16, F17, F18): SRS Versioning, Notifications, Archive, AI Compliance & Disputes'],
            ['team_id' => 1, 'student_id' => 3, 'assigned_module' => 'Module 3 (F7, F8, F10, F11, F14): Consultations, Minutes, Rubric Viva, Peer Weightage & Defense'],
            ['team_id' => 1, 'student_id' => 4, 'assigned_module' => 'Module 4 (F5, F6, F12, F13, F19): Sprint Planner, Attainment, Lab Inventory, Fines & Clearance PDF'],
        ]);
    }
}
