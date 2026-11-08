<?php

namespace App\Http\Controllers;

use App\Models\DocumentSubmission;
use App\Models\Team;
use App\Models\TeamInvitation;
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
            'invitations' => TeamInvitation::where('status', 'pending')->count(),
            'documents' => DocumentSubmission::count(),
        ];

        $recentDocuments = DocumentSubmission::with(['team', 'submitter'])->latest('id')->take(5)->get();

        // Role-specific operational summary for Sprint 1
        $roleSummary = [];
        if ($user) {
            if ($user->role === 'student') {
                $teamIds = TeamMember::where('student_id', $user->id)->pluck('team_id')->toArray();
                $myTeam = Team::with(['members.student', 'supervisor'])->whereIn('id', $teamIds)->first();
                $roleSummary = [
                    'my_team' => $myTeam,
                    'profile_completeness' => $user->profileCompleteness(),
                    'pending_invites' => TeamInvitation::where('invited_student_id', $user->id)->where('status', 'pending')->count(),
                    'team_documents' => $myTeam ? DocumentSubmission::where('team_id', $myTeam->id)->count() : 0,
                ];
            } elseif ($user->role === 'supervisor') {
                $roleSummary = [
                    'supervised_teams' => Team::where('supervisor_id', $user->id)->count(),
                    'remaining_slots' => $user->remainingSupervisorSlots(),
                    'pending_documents' => DocumentSubmission::where('faculty_review_status', 'pending')->count(),
                    'approved_documents' => DocumentSubmission::where('faculty_review_status', 'approved')->count(),
                ];
            } else {
                $roleSummary = [
                    'locked_teams' => Team::whereIn('status', ['locked', 'supervised'])->count(),
                    'forming_teams' => Team::where('status', 'forming')->count(),
                    'pending_documents' => DocumentSubmission::where('faculty_review_status', 'pending')->count(),
                    'total_students' => User::where('role', 'student')->count(),
                ];
            }
        }

        return view('dashboard.index', compact('stats', 'recentDocuments', 'roleSummary'));
    }
}
