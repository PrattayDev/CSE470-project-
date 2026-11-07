<?php

namespace App\Console\Commands;

use App\Models\MeetingMinute;
use App\Models\Sprint;
use App\Models\SystemNotification;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Feature 15 (Defend): Scheduled Artisan Command for Automated Sprint Deadline & Overdue Action Item Reminders
 */
class SendDeadlineRemindersCommand extends Command
{
    protected $signature = 'uninexus:send-deadline-reminders';

    protected $description = 'Dispatch automated reminders for upcoming sprint deadlines and overdue consultation action items (F15 & F8)';

    public function handle(): int
    {
        $dispatched = 0;
        $today = Carbon::today()->toDateString();
        $windowEnd = Carbon::today()->addDays(14)->toDateString();

        // 1. Upcoming Sprint Deadlines (F5/F6 -> F15)
        $upcomingSprints = Sprint::whereIn('status', ['planned', 'in_progress'])
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $windowEnd)
            ->get();

        foreach ($upcomingSprints as $sprint) {
            $memberIds = TeamMember::where('team_id', $sprint->team_id)->pluck('student_id')->toArray();
            $recipients = !empty($memberIds)
                ? User::whereIn('id', $memberIds)->get()
                : User::where('role', 'student')->get();

            $title = "Automated Reminder: Sprint {$sprint->sprint_number} Due ({$sprint->end_date})";

            foreach ($recipients as $user) {
                $exists = SystemNotification::where('user_id', $user->id)
                    ->where('title', $title)
                    ->exists();

                if (!$exists) {
                    SystemNotification::create([
                        'user_id' => $user->id,
                        'title' => $title,
                        'message' => "Sprint {$sprint->sprint_number} ({$sprint->sprint_goal}) is due on {$sprint->end_date}. Submit your Friday attainment report and Git commits.",
                        'type' => 'deadline',
                        'is_read' => false,
                    ]);
                    $dispatched++;
                }
            }
        }

        // 2. Overdue Consultation Action Items (F8 -> F15)
        $overdueMinutes = MeetingMinute::where('action_status', '!=', 'resolved')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->get();

        foreach ($overdueMinutes as $minute) {
            $targetUserIds = $minute->assigned_to
                ? [$minute->assigned_to]
                : TeamMember::where('team_id', $minute->team_id)->pluck('student_id')->toArray();

            $title = "Overdue Action Item #{$minute->id} (Due {$minute->due_date})";

            foreach ($targetUserIds as $uid) {
                $exists = SystemNotification::where('user_id', $uid)
                    ->where('title', $title)
                    ->exists();

                if (!$exists) {
                    SystemNotification::create([
                        'user_id' => $uid,
                        'title' => $title,
                        'message' => "Consultation follow-up item '{$minute->action_items}' passed its due date ({$minute->due_date}). Please update its status in Feature 8.",
                        'type' => 'deadline',
                        'is_read' => false,
                    ]);
                    $dispatched++;
                }
            }
        }

        $this->info("Dispatched {$dispatched} automated deadline/overdue notifications.");
        return self::SUCCESS;
    }
}
