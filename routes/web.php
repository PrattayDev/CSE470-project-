<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileSkillController;
use App\Http\Controllers\TeamFormationController;

/*
|--------------------------------------------------------------------------
| UniNexus CSE 470 MVC Web Routes (Sprint 1: Prattay F1 & F2)
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

// Protected Application Routes
Route::middleware('auth')->group(function () {
    // Main Application Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Feature 1: Multi-Role Academic Profile & Skill Matrix Management (Prattay - Sprint 1)
    Route::get('/profiles', [ProfileSkillController::class, 'index'])->name('profiles.index');
    Route::post('/profiles/update', [ProfileSkillController::class, 'update'])->name('profiles.update');

    // Feature 2: Smart Team Formation & Teammate Finder (Prattay - Sprint 1)
    Route::get('/teams', [TeamFormationController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamFormationController::class, 'storeTeam'])->middleware('role:student')->name('teams.store');
    Route::post('/teams/invite', [TeamFormationController::class, 'sendInvite'])->middleware('role:student')->name('teams.invite');
    Route::post('/teams/invitations/{invitation}/respond', [TeamFormationController::class, 'respondInvite'])->middleware('role:student')->name('teams.respond');
    Route::post('/teams/{team}/members/{member}/remove', [TeamFormationController::class, 'removeMember'])->middleware('role:student,coordinator')->name('teams.members.remove');
});
