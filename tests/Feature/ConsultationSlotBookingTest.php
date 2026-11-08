<?php

namespace Tests\Feature;

use App\Models\ConsultationSlot;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsultationSlotBookingTest extends TestCase
{
    use RefreshDatabase;

    private int $userNumber = 0;

    public function test_consultations_require_authentication_and_show_only_feature_7(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();

        $this->post(route('register.submit'), [
            'student_or_faculty_id' => 'NEW-STUDENT-01',
            'name' => 'New Feature 7 Student',
            'email' => 'new-student@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('consultations.index'));

        $this->actingAs($this->createUser('student'))
            ->get(route('consultations.index'))
            ->assertOk()
            ->assertSee('Feature 7: Consultation Slot Booking')
            ->assertDontSee('Meeting Minutes')
            ->assertDontSee('Feature 8');
    }

    public function test_supervisors_and_coordinators_can_publish_conflict_free_slots(): void
    {
        $supervisor = $this->createUser('supervisor');
        $otherSupervisor = $this->createUser('supervisor');
        $coordinator = $this->createUser('coordinator');
        $slotDate = now()->addDays(2)->toDateString();

        $this->actingAs($supervisor)
            ->post(route('consultations.slots.store'), [
                'slot_date' => $slotDate,
                'start_time' => '10:00',
                'end_time' => '10:30',
                'room_or_meet_link' => 'Room 101',
            ])
            ->assertSessionHas('success');

        $this->actingAs($supervisor)
            ->post(route('consultations.slots.store'), [
                'slot_date' => $slotDate,
                'start_time' => '10:15',
                'end_time' => '10:45',
                'room_or_meet_link' => 'Room 102',
            ])
            ->assertSessionHas('error');

        $this->actingAs($otherSupervisor)
            ->post(route('consultations.slots.store'), [
                'slot_date' => $slotDate,
                'start_time' => '10:00',
                'end_time' => '10:30',
                'room_or_meet_link' => 'Room 101',
            ])
            ->assertSessionHas('error');

        $this->actingAs($coordinator)
            ->post(route('consultations.slots.store'), [
                'supervisor_id' => $otherSupervisor->id,
                'slot_date' => $slotDate,
                'start_time' => '11:00',
                'end_time' => '11:30',
                'room_or_meet_link' => 'Room 103',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('consultation_slots', 2);
        $this->assertDatabaseHas('consultation_slots', [
            'supervisor_id' => $otherSupervisor->id,
            'room_or_meet_link' => 'Room 103',
            'status' => 'available',
        ]);
    }

    public function test_students_can_only_book_their_team_and_cancel_their_own_booking(): void
    {
        $student = $this->createUser('student');
        $supervisor = $this->createUser('supervisor');
        $team = Team::create(['group_name' => 'Student Team']);
        $otherTeam = Team::create(['group_name' => 'Other Team']);
        TeamMember::create(['team_id' => $team->id, 'student_id' => $student->id]);

        $slot = $this->createSlot($supervisor, '10:00', '10:30');
        $nextSlot = $this->createSlot($supervisor, '11:00', '11:30');
        $booking = [
            'booked_by_team_id' => $team->id,
            'agenda' => 'Review the project plan',
        ];

        $this->actingAs($student)
            ->post(route('consultations.slots.book', $slot), [
                'booked_by_team_id' => $otherTeam->id,
                'agenda' => $booking['agenda'],
            ])
            ->assertSessionHas('error');
        $this->assertSame('available', $slot->fresh()->status);

        $this->actingAs($student)
            ->post(route('consultations.slots.book', $slot), $booking)
            ->assertSessionHas('success');
        $this->assertDatabaseHas('consultation_slots', [
            'id' => $slot->id,
            'booked_by_team_id' => $team->id,
            'agenda' => $booking['agenda'],
            'status' => 'booked',
        ]);

        $this->actingAs($student)
            ->post(route('consultations.slots.book', $nextSlot), $booking)
            ->assertSessionHas('error');
        $this->assertSame('available', $nextSlot->fresh()->status);

        $this->actingAs($student)
            ->post(route('consultations.slots.cancel', $slot))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('consultation_slots', [
            'id' => $slot->id,
            'booked_by_team_id' => null,
            'agenda' => null,
            'status' => 'available',
        ]);
    }

    public function test_students_cannot_publish_slots(): void
    {
        $student = $this->createUser('student');

        $this->actingAs($student)
            ->post(route('consultations.slots.store'), [
                'slot_date' => now()->addDays(2)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '10:30',
                'room_or_meet_link' => 'Room 101',
            ])
            ->assertRedirect(route('consultations.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('consultation_slots', 0);
    }

    public function test_seed_data_and_database_tables_are_scoped_to_feature_7(): void
    {
        $this->seed();

        $student = User::where('email', 'student.f7@uninexus.test')->firstOrFail();
        $team = Team::where('group_name', 'Feature 7 Sample Team')->firstOrFail();

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('teams', 1);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'student_id' => $student->id,
        ]);
        $this->assertFalse(Schema::hasTable('meeting_minutes'));
        $this->assertFalse(Schema::hasTable('system_notifications'));
        $this->assertFalse(Schema::hasTable('proposals'));
    }

    private function createUser(string $role): User
    {
        $number = ++$this->userNumber;

        return User::create([
            'student_or_faculty_id' => strtoupper($role) . '-' . $number,
            'name' => ucfirst($role) . ' ' . $number,
            'email' => $role . $number . '@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }

    private function createSlot(User $supervisor, string $startTime, string $endTime): ConsultationSlot
    {
        return ConsultationSlot::create([
            'supervisor_id' => $supervisor->id,
            'slot_date' => now()->addDays(2)->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room_or_meet_link' => 'Room ' . $startTime,
            'status' => 'available',
        ]);
    }
}
