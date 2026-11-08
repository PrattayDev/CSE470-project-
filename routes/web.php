<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| UniNexus CSE 470 MVC Web Routes (Sprint 1 Foundation)
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
});
