<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BookingRequestController;
use App\Http\Controllers\EntertainerController;
use App\Http\Controllers\EntertainerDashboardController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

Route::get('/kinderentertainers', [EntertainerController::class, 'index'])->name('entertainers.index');
Route::get('/kinderentertainers/{entertainer:slug}', [EntertainerController::class, 'show'])->name('entertainers.show');
Route::get('/aanvragen', [BookingRequestController::class, 'create'])->name('booking-requests.general.create');
Route::post('/aanvragen', [BookingRequestController::class, 'store'])->name('booking-requests.general.store');
Route::get('/kinderentertainers/{entertainer:slug}/aanvragen', [BookingRequestController::class, 'create'])->name('booking-requests.create');
Route::post('/kinderentertainers/{entertainer:slug}/aanvragen', [BookingRequestController::class, 'store'])->name('booking-requests.store');
Route::get('/aanvraag-bedankt', [BookingRequestController::class, 'thanks'])->name('booking-requests.thanks');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/setup', [SetupController::class, 'create'])->name('setup');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

Route::middleware('auth')->prefix('dashboard')->group(function (): void {
    Route::get('/', [EntertainerDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/profiel', [EntertainerDashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::patch('/skills', [EntertainerDashboardController::class, 'updateSkills'])->name('dashboard.skills.update');
    Route::post('/beschikbaarheid', [EntertainerDashboardController::class, 'storeAvailability'])->name('dashboard.availabilities.store');
    Route::patch('/beschikbaarheid/{availability}', [EntertainerDashboardController::class, 'updateAvailability'])->name('dashboard.availabilities.update');
    Route::delete('/beschikbaarheid/{availability}', [EntertainerDashboardController::class, 'destroyAvailability'])->name('dashboard.availabilities.destroy');
    Route::post('/tarieven', [EntertainerDashboardController::class, 'storeRate'])->name('dashboard.rates.store');
    Route::patch('/tarieven/{rate}', [EntertainerDashboardController::class, 'updateRate'])->name('dashboard.rates.update');
    Route::delete('/tarieven/{rate}', [EntertainerDashboardController::class, 'destroyRate'])->name('dashboard.rates.destroy');
    Route::patch('/aanvragen/{bookingRequest}/status', [EntertainerDashboardController::class, 'updateBookingStatus'])
        ->name('dashboard.booking-requests.status');
});
