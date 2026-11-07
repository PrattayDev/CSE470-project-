<?php

namespace App\Http\Controllers;

use App\Models\Sprint;
use App\Models\SystemNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Feature 15: Automated Notification & Deadline Reminder System (Rownak)
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        // Automated Deadline Check: Generate upcoming sprint deadline reminders automatically for this user
        $this->dispatchAutomatedSprintDeadlineReminders($user);

        // Strict user-scoped inbox: users only see and manage their own notifications
        $notifications = SystemNotification::with('user')
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        return view('notifications.index', compact('notifications', 'unreadCount', 'user'));
    }

    public function broadcastReminder(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null && in_array($user->role, ['supervisor', 'coordinator'], true), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:180',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:deadline,approval,penalty,viva,general',
            'target_role' => 'nullable|in:all,student,supervisor,lab_admin,coordinator',
            'target_section' => 'nullable|string|max:20',
        ]);

        $targetRole = $validated['target_role'] ?? 'all';
        $targetSection = trim((string) ($validated['target_section'] ?? ''));

        $users = User::query()
            ->when($targetRole !== 'all', fn ($q) => $q->where('role', $targetRole))
            ->when($targetSection !== '', fn ($q) => $q->where('section', $targetSection))
            ->get();

        foreach ($users as $recipient) {
            SystemNotification::create([
                'user_id' => $recipient->id,
                'title' => $validated['title'],
                'message' => $validated['message'],
                'type' => $validated['type'],
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'Feature 15: Targeted broadcast delivered to ' . $users->count() . ' recipient(s).');
    }

    public function markRead(Request $request, SystemNotification $notification)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if ((int) $notification->user_id !== (int) $user->id) {
            return back()->with('error', 'Access denied: You can only mark your own notifications as read.');
        }

        $notification->update(['is_read' => true]);
        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        SystemNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Feature 15: All personal notifications marked as read.');
    }

    private function dispatchAutomatedSprintDeadlineReminders(User $user): void
    {
        $upcomingSprints = Sprint::whereIn('status', ['planned', 'in_progress'])
            ->whereDate('end_date', '>=', Carbon::today()->toDateString())
            ->whereDate('end_date', '<=', Carbon::today()->addDays(14)->toDateString())
            ->get();

        foreach ($upcomingSprints as $sprint) {
            $title = "Automated Reminder: Sprint {$sprint->sprint_number} Due ({$sprint->end_date})";
            $alreadyNotified = SystemNotification::where('user_id', $user->id)
                ->where('title', $title)
                ->exists();

            if (!$alreadyNotified) {
                SystemNotification::create([
                    'user_id' => $user->id,
                    'title' => $title,
                    'message' => "Sprint {$sprint->sprint_number} ({$sprint->sprint_goal}) is due on {$sprint->end_date}. Ensure all Git commits and Friday attainment reports are submitted.",
                    'type' => 'deadline',
                    'is_read' => false,
                ]);
            }
        }
    }
}
