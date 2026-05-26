<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BookingRequestController;
use App\Http\Controllers\EntertainerController;
use App\Http\Controllers\EntertainerDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

Route::get('/kinderentertainers', [EntertainerController::class, 'index'])->name('entertainers.index');
Route::get('/kinderentertainers/{entertainer:slug}', [EntertainerController::class, 'show'])->name('entertainers.show');
Route::get('/kinderentertainers/{entertainer:slug}/aanvragen', [BookingRequestController::class, 'create'])->name('booking-requests.create');
Route::post('/kinderentertainers/{entertainer:slug}/aanvragen', [BookingRequestController::class, 'store'])->name('booking-requests.store');
Route::get('/aanvraag-bedankt', [BookingRequestController::class, 'thanks'])->name('booking-requests.thanks');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('dashboard')->group(function (): void {
    Route::get('/', [EntertainerDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/aanvragen/{bookingRequest}/status', [EntertainerDashboardController::class, 'updateBookingStatus'])
        ->name('dashboard.booking-requests.status');
});
