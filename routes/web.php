<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BookingQuoteController;
use App\Http\Controllers\BookingRequestController;
use App\Http\Controllers\BookingRequestMatchController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\EntertainerController;
use App\Http\Controllers\EntertainerDashboardController;
use App\Http\Controllers\PublicLegalDocumentController;
use App\Http\Controllers\ReviewController;
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
Route::get('/aanvragen/{bookingRequest}/matches/{token}', [BookingRequestMatchController::class, 'index'])->name('booking-requests.matches.index');
Route::post('/aanvragen/{bookingRequest}/matches/{match}/kiezen', [BookingRequestMatchController::class, 'select'])->name('booking-requests.matches.select');
Route::get('/reviews/{token}', [ReviewController::class, 'create'])->name('reviews.create');
Route::post('/reviews/{token}', [ReviewController::class, 'store'])->name('reviews.store');
Route::get('/review-bedankt', [ReviewController::class, 'thanks'])->name('reviews.thanks');
Route::get('/algemene-voorwaarden', [PublicLegalDocumentController::class, 'terms'])->name('legal.terms');
Route::get('/privacyverklaring', [PublicLegalDocumentController::class, 'privacy'])->name('legal.privacy');
Route::get('/cookieverklaring', [PublicLegalDocumentController::class, 'cookies'])->name('legal.cookies');
Route::get('/offertes/{token}', [BookingQuoteController::class, 'show'])->name('booking-quotes.show');
Route::post('/offertes/{token}/akkoord', [BookingQuoteController::class, 'accept'])->name('booking-quotes.accept');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/setup', [SetupController::class, 'create'])->name('setup');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

Route::middleware('auth')->prefix('dashboard')->group(function (): void {
    Route::get('/', [EntertainerDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/profiel', [EntertainerDashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::patch('/facturatie', [EntertainerDashboardController::class, 'updateBilling'])->name('dashboard.billing.update');
    Route::patch('/integraties/{integration}', [EntertainerDashboardController::class, 'updateIntegration'])->name('dashboard.integrations.update');
    Route::patch('/skills', [EntertainerDashboardController::class, 'updateSkills'])->name('dashboard.skills.update');
    Route::post('/beschikbaarheid', [EntertainerDashboardController::class, 'storeAvailability'])->name('dashboard.availabilities.store');
    Route::patch('/beschikbaarheid/{availability}', [EntertainerDashboardController::class, 'updateAvailability'])->name('dashboard.availabilities.update');
    Route::delete('/beschikbaarheid/{availability}', [EntertainerDashboardController::class, 'destroyAvailability'])->name('dashboard.availabilities.destroy');
    Route::post('/beschikbaarheid/herhaling', [EntertainerDashboardController::class, 'storeAvailabilityRule'])->name('dashboard.availability-rules.store');
    Route::patch('/beschikbaarheid/herhaling/{availabilityRule}', [EntertainerDashboardController::class, 'updateAvailabilityRule'])->name('dashboard.availability-rules.update');
    Route::delete('/beschikbaarheid/herhaling/{availabilityRule}', [EntertainerDashboardController::class, 'destroyAvailabilityRule'])->name('dashboard.availability-rules.destroy');
    Route::post('/tarieven', [EntertainerDashboardController::class, 'storeRate'])->name('dashboard.rates.store');
    Route::patch('/tarieven/{rate}', [EntertainerDashboardController::class, 'updateRate'])->name('dashboard.rates.update');
    Route::delete('/tarieven/{rate}', [EntertainerDashboardController::class, 'destroyRate'])->name('dashboard.rates.destroy');
    Route::patch('/aanvragen/{bookingRequest}/status', [EntertainerDashboardController::class, 'updateBookingStatus'])
        ->name('dashboard.booking-requests.status');
    Route::post('/aanvragen/{bookingRequest}/tijdlijn', [EntertainerDashboardController::class, 'storeBookingRequestEvent'])
        ->name('dashboard.booking-requests.events.store');
    Route::post('/aanvragen/{bookingRequest}/offerte', [EntertainerDashboardController::class, 'createBookingQuote'])
        ->name('dashboard.booking-requests.quote');
    Route::patch('/matches/{match}/reactie', [EntertainerDashboardController::class, 'updateMatchResponse'])
        ->name('dashboard.matches.response');
});

Route::middleware('auth')->prefix('klantportaal')->name('customer-portal.')->group(function (): void {
    Route::get('/', [CustomerPortalController::class, 'index'])->name('index');
    Route::get('/aanvragen/{bookingRequest}', [CustomerPortalController::class, 'show'])->name('show');
    Route::patch('/aanvragen/{bookingRequest}', [CustomerPortalController::class, 'update'])->name('update');
    Route::post('/aanvragen/{bookingRequest}/berichten', [CustomerPortalController::class, 'storeMessage'])->name('messages.store');
    Route::post('/aanvragen/{bookingRequest}/annuleren', [CustomerPortalController::class, 'cancel'])->name('cancel');
    Route::post('/aanvragen/{bookingRequest}/offerte-accepteren', [CustomerPortalController::class, 'acceptQuote'])->name('accept-quote');
    Route::get('/aanvragen/{bookingRequest}/document', [CustomerPortalController::class, 'download'])->name('download');
    Route::post('/favorieten/{entertainer}', [CustomerPortalController::class, 'favorite'])->name('favorites.store');
    Route::delete('/favorieten/{entertainer}', [CustomerPortalController::class, 'unfavorite'])->name('favorites.destroy');
});
