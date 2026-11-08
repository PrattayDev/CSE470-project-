<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('consultations.index'));
    Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    Route::post('/consultations/slots', [ConsultationController::class, 'storeSlot'])
        ->middleware('role:supervisor,coordinator')
        ->name('consultations.slots.store');
    Route::post('/consultations/slots/{slot}/book', [ConsultationController::class, 'bookSlot'])
        ->middleware('role:student')
        ->name('consultations.slots.book');
    Route::post('/consultations/slots/{slot}/cancel', [ConsultationController::class, 'cancelBooking'])
        ->middleware('role:student,supervisor,coordinator')
        ->name('consultations.slots.cancel');
});
