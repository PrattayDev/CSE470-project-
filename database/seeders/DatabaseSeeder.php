<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password123');
        $accounts = [
            [
                'student_or_faculty_id' => 'F7-STUDENT-01',
                'name' => 'Feature 7 Student',
                'email' => 'student.f7@uninexus.test',
                'role' => 'student',
            ],
            [
                'student_or_faculty_id' => 'F7-SUPERVISOR-01',
                'name' => 'Feature 7 Supervisor',
                'email' => 'supervisor.f7@uninexus.test',
                'role' => 'supervisor',
            ],
            [
                'student_or_faculty_id' => 'F7-COORDINATOR-01',
                'name' => 'Feature 7 Coordinator',
                'email' => 'coordinator.f7@uninexus.test',
                'role' => 'coordinator',
            ],
        ];

        DB::transaction(function () use ($accounts, $now, $password): void {
            foreach ($accounts as $account) {
                $attributes = array_merge($account, [
                    'password' => $password,
                    'updated_at' => $now,
                ]);
                $existingUserId = DB::table('users')
                    ->where('email', $account['email'])
                    ->value('id');

                if ($existingUserId) {
                    DB::table('users')->where('id', $existingUserId)->update($attributes);
                } else {
                    DB::table('users')->insert($attributes + ['created_at' => $now]);
                }
            }

            $studentId = DB::table('users')
                ->where('email', 'student.f7@uninexus.test')
                ->value('id');
            $teamName = 'Feature 7 Sample Team';
            $teamAttributes = [
                'group_name' => $teamName,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('teams', 'section')) {
                $teamAttributes['section'] = '09';
            }
            if (Schema::hasColumn('teams', 'leader_id')) {
                $teamAttributes['leader_id'] = $studentId;
            }
            if (Schema::hasColumn('teams', 'status')) {
                $teamAttributes['status'] = 'forming';
            }
            if (Schema::hasColumn('teams', 'preferred_stack')) {
                $teamAttributes['preferred_stack'] = 'Laravel MVC + MySQL';
            }

            $teamId = DB::table('teams')->where('group_name', $teamName)->value('id');
            if ($teamId) {
                DB::table('teams')->where('id', $teamId)->update($teamAttributes);
            } else {
                $teamId = DB::table('teams')->insertGetId($teamAttributes + ['created_at' => $now]);
            }

            DB::table('team_members')->updateOrInsert(
                ['team_id' => $teamId, 'student_id' => $studentId],
                ['student_id' => $studentId]
            );
        });
    }
}
