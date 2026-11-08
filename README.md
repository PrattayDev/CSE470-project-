# UniNexus — Feature 7

UniNexus is a Laravel 10 MVC application scoped to **Feature 7: Consultation Slot Booking & Meeting Scheduler**.

## Feature 7

- Supervisors publish consultation slots; coordinators can publish slots for a supervisor.
- The app rejects overlapping slots for the same supervisor or room/meeting link.
- Students can book an available slot for a team they belong to. A team cannot book an occupied slot or hold multiple upcoming slots with the same supervisor.
- Students can cancel their own team's booking; supervisors can cancel bookings on their own slots; coordinators can cancel any booking.
- Users must sign in, and role middleware restricts slot publishing and booking actions.

## Shared infrastructure retained

Authentication and the shared Blade layout are retained. The minimum feature data model is:

- `users`: login identity and role
- `teams`: team name displayed with a booking
- `team_members`: verifies which teams a student may book for
- `consultation_slots`: published slots, bookings, and agenda

Feature 7 intentionally depends on pre-existing team and membership records. The seed data creates one sample student, supervisor, coordinator, team, and membership so the feature can be exercised immediately. Team-formation functionality is not included.

## Local setup

1. Install PHP dependencies with `composer install` and configure `.env` for a disposable MySQL database.
2. Install the stylesheet build dependency and regenerate the CSS when needed:

   ```sh
   npm install
   npm run build
   ```

### Seed accounts

All seed accounts use the password `password123`:

| Role | Email |
|---|---|
| Student | `student.f7@uninexus.test` |
| Supervisor | `supervisor.f7@uninexus.test` |
| Coordinator | `coordinator.f7@uninexus.test` |

The sample student is a member of the seeded sample team. Seeding is safe to rerun: it creates missing accounts/team membership and resets the demo-account passwords to `password123`.

## Verification

The automated Feature 7 tests use an in-memory SQLite database:

```sh
php artisan test
```
