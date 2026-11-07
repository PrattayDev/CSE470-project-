<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSlot;
use App\Models\DocumentSubmission;
use App\Models\EquipmentRequisition;
use App\Models\LabEquipment;
use App\Models\Proposal;
use App\Models\ScopeChangeRequest;
use App\Models\Sprint;
use App\Models\SystemNotification;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'users' => User::count(),
            'teams' => Team::count(),
            'proposals' => Proposal::count(),
            'sprints' => Sprint::count(),
            'equipments' => LabEquipment::sum('available_quantity'),
        ];

        $recentSprints = Sprint::with('team')->latest()->take(4)->get();
        $notifications = SystemNotification::where('user_id', $user?->id)->latest()->take(5)->get();

        // Role-specific operational summary for the logged-in user
        $roleSummary = [];
        if ($user) {
            if ($user->role === 'student') {
                $teamIds = TeamMember::where('student_id', $user->id)->pluck('team_id')->toArray();
                $myTeam = Team::with(['members.student', 'supervisor', 'proposals'])->whereIn('id', $teamIds)->first();
                $roleSummary = [
                    'my_team' => $myTeam,
                    'active_sprint' => $myTeam ? Sprint::where('team_id', $myTeam->id)->orderByDesc('sprint_number')->first() : null,
                    'next_consultation' => $myTeam ? ConsultationSlot::where('booked_by_team_id', $myTeam->id)->whereDate('slot_date', '>=', now()->toDateString())->orderBy('slot_date')->first() : null,
                    'active_loans' => $myTeam ? EquipmentRequisition::where('team_id', $myTeam->id)->whereIn('status', ['requested', 'issued', 'overdue'])->count() : 0,
                    'outstanding_fine' => $myTeam ? (float) EquipmentRequisition::where('team_id', $myTeam->id)->sum('calculated_fine_bdt') : 0.0,
                ];
            } elseif ($user->role === 'supervisor') {
                $roleSummary = [
                    'pending_proposals' => Proposal::where('supervisor_id', $user->id)->where('status', 'pending')->count(),
                    'pending_documents' => DocumentSubmission::where('faculty_review_status', 'pending')->count(),
                    'submitted_sprints' => Sprint::where('status', 'submitted')->count(),
                    'today_consultations' => ConsultationSlot::where('supervisor_id', $user->id)->whereDate('slot_date', '>=', now()->toDateString())->where('status', 'booked')->count(),
                ];
            } elseif ($user->role === 'lab_admin') {
                $roleSummary = [
                    'pending_requisitions' => EquipmentRequisition::where('status', 'requested')->count(),
                    'active_loans' => EquipmentRequisition::whereIn('status', ['issued', 'overdue'])->count(),
                    'unpaid_fines_bdt' => (float) EquipmentRequisition::sum('calculated_fine_bdt'),
                    'low_stock_items' => LabEquipment::whereColumn('available_quantity', '<=', 'total_quantity')->get()->filter(fn ($e) => $e->isLowStock())->count(),
                ];
            } elseif ($user->role === 'coordinator') {
                $roleSummary = [
                    'pending_disputes' => ScopeChangeRequest::whereIn('status', ['pending', 'under_review'])->count(),
                    'pending_proposals' => Proposal::where('status', 'pending')->count(),
                    'locked_teams' => Team::where('status', 'locked')->count(),
                    'unread_alerts' => SystemNotification::where('is_read', false)->count(),
                ];
            }
        }

        return view('dashboard.index', compact('stats', 'recentSprints', 'notifications', 'roleSummary'));
    }
}
