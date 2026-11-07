@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-1">
    <h2 class="text-2xl font-bold">Feature 15: Automated Notification & Deadline Reminder System</h2>
    <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1 rounded-full">My Unread Alerts: {{ $unreadCount ?? 0 }}</span>
</div>
<p class="text-sm text-slate-500 mb-6">Automatically generates upcoming sprint deadline reminders and delivers cohort-wide alerts to your personal inbox.</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Broadcast Cohort Reminder</h3>
        @if(in_array(auth()->user()->role, ['supervisor', 'coordinator']))
        <form method="POST" action="{{ route('notifications.broadcast') }}" class="space-y-3 text-sm">
            @csrf
            <input type="text" name="title" placeholder="Sprint 1 Submission Reminder (8 Nov)" required class="w-full border rounded px-3 py-1.5">
            <select name="type" class="w-full border rounded px-3 py-1.5">
                <option value="deadline">Deadline Alert</option>
                <option value="approval">Proposal Approval Notice</option>
                <option value="viva">Viva Schedule Notice</option>
                <option value="penalty">Lab Penalty Alert</option>
                <option value="general">General Announcement</option>
            </select>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <label class="block text-slate-500 mb-1">Target Role</label>
                    <select name="target_role" class="w-full border rounded px-2 py-1.5">
                        <option value="all">All Roles</option>
                        <option value="student">Students Only</option>
                        <option value="supervisor">Supervisors Only</option>
                        <option value="lab_admin">Lab Admins Only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Section (Optional)</label>
                    <input type="text" name="target_section" placeholder="e.g. 09" class="w-full border rounded px-2 py-1.5">
                </div>
            </div>
            <textarea name="message" rows="3" placeholder="Ensure all 4 members have pushed their Git commits before Friday 11:59 PM." required class="w-full border rounded px-3 py-1.5"></textarea>
            <button type="submit" class="w-full bg-rose-600 text-white py-2 rounded font-semibold">Broadcast Alert</button>
        </form>
        @else
        <div class="p-3 bg-slate-50 border rounded text-xs text-slate-600">
            Automated sprint deadline alerts are dispatched to your inbox automatically. Cohort-wide broadcasts are restricted to Supervisors and the Course Coordinator.
        </div>
        @endif
    </div>
    <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold">My Personal Notification Inbox ({{ auth()->user()->name }})</h3>
            @if(($unreadCount ?? 0) > 0)
            <form method="POST" action="{{ route('notifications.read_all') }}">
                @csrf
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-3 py-1 rounded text-xs font-semibold">
                    Mark All as Read
                </button>
            </form>
            @endif
        </div>
        @forelse($notifications as $n)
            <div class="p-3 border rounded mb-2 text-sm flex justify-between items-center {{ $n->is_read ? 'bg-slate-50 opacity-75' : 'bg-white border-indigo-200' }}">
                <div>
                    <span class="uppercase text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800">{{ $n->type }}</span>
                    <strong class="ml-1">{{ $n->title }}</strong>
                    <p class="text-xs text-slate-600 mt-0.5">{{ $n->message }}</p>
                </div>
                <div>
                    @if(!$n->is_read)
                    <form method="POST" action="{{ route('notifications.read', $n) }}">
                        @csrf
                        <button type="submit" class="bg-indigo-600 text-white px-2.5 py-1 rounded text-xs font-medium">Mark Read</button>
                    </form>
                    @else
                    <span class="text-xs text-emerald-600 font-bold">Read</span>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-xs text-slate-500">No notifications in your inbox yet.</p>
        @endforelse
    </div>
</div>
@endsection
