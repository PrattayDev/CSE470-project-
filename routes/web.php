<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileSkillController;
use App\Http\Controllers\TeamFormationController;
use App\Http\Controllers\ProposalBiddingController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\LabInventoryController;
use App\Http\Controllers\DefenseScheduleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\ScopeDisputeController;
use App\Http\Controllers\ClearanceController;
use App\Http\Controllers\AnalyticsController;

/*
|--------------------------------------------------------------------------
| UniNexus CSE 470 MVC Web Routes (Auth + 20 Substantive Features)
|--------------------------------------------------------------------------
*/

// Mandatory Authentication Module (Excluded from 20 features count)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// All 20 Substantive Feature Routes (Protected by Authentication & Role-Based Access Control)
Route::middleware('auth')->group(function () {
    // Main Application Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Feature 1: Multi-Role Academic Profile & Skill Matrix Management (Prattay - Sprint 1)
    Route::get('/profiles', [ProfileSkillController::class, 'index'])->name('profiles.index');
    Route::post('/profiles/update', [ProfileSkillController::class, 'update'])->name('profiles.update');

    // Feature 2: Smart Team Formation & Teammate Finder (Prattay)
    Route::get('/teams', [TeamFormationController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamFormationController::class, 'storeTeam'])->middleware('role:student')->name('teams.store');
    Route::post('/teams/invite', [TeamFormationController::class, 'sendInvite'])->middleware('role:student')->name('teams.invite');
    Route::post('/teams/invitations/{invitation}/respond', [TeamFormationController::class, 'respondInvite'])->middleware('role:student')->name('teams.respond');
    Route::post('/teams/{team}/members/{member}/remove', [TeamFormationController::class, 'removeMember'])->middleware('role:student,coordinator')->name('teams.members.remove');

    // Feature 3: Supervisor Quota & Proposal Bidding + Feature 4: Duplicate Topic Similarity Checker (Prattay)
    Route::get('/proposals', [ProposalBiddingController::class, 'index'])->name('proposals.index');
    Route::post('/proposals/check-similarity', [ProposalBiddingController::class, 'checkSimilarity'])->name('proposals.check_similarity');
    Route::post('/proposals', [ProposalBiddingController::class, 'store'])->middleware('role:student')->name('proposals.store');
    Route::post('/proposals/{proposal}/review', [ProposalBiddingController::class, 'review'])->middleware('role:supervisor,coordinator')->name('proposals.review');

    // Feature 5: Weekly Sprint Planning + Feature 6: Sprint Attainment & Review Workflow (Siddique)
    Route::get('/sprints', [SprintController::class, 'index'])->name('sprints.index');
    Route::post('/sprints', [SprintController::class, 'storeSprint'])->middleware('role:student,supervisor,coordinator')->name('sprints.store');
    Route::post('/sprints/tasks', [SprintController::class, 'storeTask'])->middleware('role:student,supervisor,coordinator')->name('sprints.tasks.store');
    Route::post('/sprints/tasks/{task}/status', [SprintController::class, 'updateTaskStatus'])->middleware('role:student,supervisor,coordinator')->name('sprints.tasks.status');
    Route::post('/sprints/{sprint}/attainment', [SprintController::class, 'submitAttainment'])->middleware('role:student,supervisor,coordinator')->name('sprints.attainment');
    Route::post('/sprints/{sprint}/review', [SprintController::class, 'reviewSprint'])->middleware('role:supervisor,coordinator')->name('sprints.review');

    // Feature 7: Consultation Slot Booking + Feature 8: Meeting Minutes & Action Item Tracker (Samara)
    Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    Route::post('/consultations/slots', [ConsultationController::class, 'storeSlot'])->middleware('role:supervisor,coordinator')->name('consultations.slots.store');
    Route::post('/consultations/slots/{slot}/book', [ConsultationController::class, 'bookSlot'])->middleware('role:student')->name('consultations.slots.book');
    Route::post('/consultations/slots/{slot}/cancel', [ConsultationController::class, 'cancelBooking'])->middleware('role:student,supervisor,coordinator')->name('consultations.slots.cancel');
    Route::post('/consultations/minutes', [ConsultationController::class, 'storeMinutes'])->middleware('role:student,supervisor,coordinator')->name('consultations.minutes.store');
    Route::post('/consultations/minutes/{minute}/status', [ConsultationController::class, 'updateMinuteStatus'])->middleware('role:student,supervisor,coordinator')->name('consultations.minutes.status');

    // Feature 9: Document Version Control & SRS/Report Submission Portal (Rownak - Sprint 1)
    Route::get('/documents', [DocumentVersionController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentVersionController::class, 'store'])->middleware('role:student')->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentVersionController::class, 'download'])->name('documents.download');
    Route::post('/documents/{document}/review', [DocumentVersionController::class, 'review'])->middleware('role:supervisor,coordinator')->name('documents.review');

    // Feature 10: Rubric-Based Evaluation (15 Marks) + Feature 11: Peer Evaluation Weightage Calculator (Samara)
    Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::post('/evaluations/rubric', [EvaluationController::class, 'storeRubric'])->middleware('role:supervisor,coordinator')->name('evaluations.rubric.store');
    Route::post('/evaluations/rubric/{rubric}/finalize', [EvaluationController::class, 'finalizeRubric'])->middleware('role:coordinator')->name('evaluations.rubric.finalize');
    Route::post('/evaluations/peer', [EvaluationController::class, 'storePeerEvaluation'])->middleware('role:student')->name('evaluations.peer.store');

    // Feature 12: Hardware & Lab Equipment Inventory + Feature 13: Requisition & Return Penalty Workflow (Siddique)
    Route::get('/inventory', [LabInventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/equipment', [LabInventoryController::class, 'storeEquipment'])->middleware('role:lab_admin,coordinator')->name('inventory.equipment.store');
    Route::post('/inventory/equipment/{equipment}/update', [LabInventoryController::class, 'updateEquipment'])->middleware('role:lab_admin,coordinator')->name('inventory.equipment.update');
    Route::post('/inventory/requisition', [LabInventoryController::class, 'requestEquipment'])->middleware('role:student')->name('inventory.requisition.store');
    Route::post('/inventory/requisition/{requisition}/approve', [LabInventoryController::class, 'approveRequisition'])->middleware('role:lab_admin,coordinator')->name('inventory.requisition.approve');
    Route::post('/inventory/requisition/{requisition}/return', [LabInventoryController::class, 'returnEquipment'])->middleware('role:lab_admin,coordinator')->name('inventory.requisition.return');
    Route::post('/inventory/requisition/{requisition}/pay-fine', [LabInventoryController::class, 'payFine'])->middleware('role:lab_admin,coordinator')->name('inventory.requisition.pay');

    // Feature 14: Defense/Viva Room & Panel Scheduling Engine (Samara)
    Route::get('/defense', [DefenseScheduleController::class, 'index'])->name('defense.index');
    Route::post('/defense', [DefenseScheduleController::class, 'store'])->middleware('role:supervisor,coordinator')->name('defense.store');

    // Feature 15: Automated Notification & Deadline Reminder System (Rownak)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/broadcast', [NotificationController::class, 'broadcastReminder'])->middleware('role:supervisor,coordinator')->name('notifications.broadcast');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Feature 16: Project Showcase & Digital Archive Repository (Rownak)
    Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
    Route::post('/archive', [ArchiveController::class, 'store'])->middleware('role:supervisor,coordinator')->name('archive.store');
    Route::post('/archive/from-team/{team}', [ArchiveController::class, 'archiveTeam'])->middleware('role:supervisor,coordinator')->name('archive.from_team');

    // Feature 17: Plagiarism & AI-Disclosure Compliance Tracker (Rownak)
    Route::get('/compliance', [ComplianceController::class, 'index'])->name('compliance.index');
    Route::post('/compliance', [ComplianceController::class, 'store'])->middleware('role:student')->name('compliance.store');
    Route::post('/compliance/{disclosure}/verify', [ComplianceController::class, 'verify'])->middleware('role:supervisor,coordinator')->name('compliance.verify');

    // Feature 18: Group Dispute & Scope Change Request Module (Rownak)
    Route::get('/disputes', [ScopeDisputeController::class, 'index'])->name('disputes.index');
    Route::post('/disputes', [ScopeDisputeController::class, 'store'])->middleware('role:student')->name('disputes.store');
    Route::post('/disputes/{scopeChangeRequest}/decide', [ScopeDisputeController::class, 'decide'])->middleware('role:coordinator')->name('disputes.decide');

    // Feature 19: Automated Certificate & Clearance Sheet Generator (Siddique)
    Route::get('/clearance', [ClearanceController::class, 'index'])->name('clearance.index');
    Route::post('/clearance/generate', [ClearanceController::class, 'generate'])->middleware('role:lab_admin,supervisor,coordinator')->name('clearance.generate');
    Route::get('/clearance/{certificate}/print', [ClearanceController::class, 'printView'])->name('clearance.print');

    // Feature 20: Coordinator Analytics & Cohort Performance Dashboard (Prattay)
    Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('role:coordinator')->name('analytics.index');
});
