<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookingQuoteController;
use App\Http\Controllers\BookingRequestController;
use App\Http\Controllers\BookingRequestMatchController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\EntertainerController;
use App\Http\Controllers\EntertainerDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\PublicLegalDocumentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::get('/kinderentertainers', [EntertainerController::class, 'index'])->name('entertainers.index');
Route::get('/kinderentertainers/{entertainer:slug}', [EntertainerController::class, 'show'])->name('entertainers.show');
Route::get('/aanvragen', [BookingRequestController::class, 'create'])->name('booking-requests.general.create');
Route::post('/aanvragen', [BookingRequestController::class, 'store'])->middleware('throttle:10,1')->name('booking-requests.general.store');
Route::get('/kinderentertainers/{entertainer:slug}/aanvragen', [BookingRequestController::class, 'create'])->name('booking-requests.create');
Route::post('/kinderentertainers/{entertainer:slug}/aanvragen', [BookingRequestController::class, 'store'])->middleware('throttle:10,1')->name('booking-requests.store');
Route::get('/aanvraag-bedankt', [BookingRequestController::class, 'thanks'])->name('booking-requests.thanks');
Route::get('/aanvragen/{bookingRequest}/matches/{token}', [BookingRequestMatchController::class, 'index'])->name('booking-requests.matches.index');
Route::post('/aanvragen/{bookingRequest}/matches/{match}/kiezen', [BookingRequestMatchController::class, 'select'])->middleware('throttle:20,1')->name('booking-requests.matches.select');
Route::get('/reviews/{token}', [ReviewController::class, 'create'])->name('reviews.create');
Route::post('/reviews/{token}', [ReviewController::class, 'store'])->middleware('throttle:5,1')->name('reviews.store');
Route::get('/review-bedankt', [ReviewController::class, 'thanks'])->name('reviews.thanks');
Route::get('/algemene-voorwaarden', [PublicLegalDocumentController::class, 'terms'])->name('legal.terms');
Route::get('/privacyverklaring', [PublicLegalDocumentController::class, 'privacy'])->name('legal.privacy');
Route::get('/cookieverklaring', [PublicLegalDocumentController::class, 'cookies'])->name('legal.cookies');
Route::get('/offertes/{token}', [BookingQuoteController::class, 'show'])->name('booking-quotes.show');
Route::post('/offertes/{token}/akkoord', [BookingQuoteController::class, 'accept'])->middleware('throttle:10,1')->name('booking-quotes.accept');
Route::post('/webhooks/betalingen/{provider}', PaymentWebhookController::class)->middleware('throttle:120,1')->name('webhooks.payments');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware(['guest', 'throttle:login']);
Route::get('/registreren', [RegisteredUserController::class, 'create'])->middleware('guest')->name('register');
Route::post('/registreren', [RegisteredUserController::class, 'store'])->middleware(['guest', 'throttle:register']);
Route::get('/wachtwoord-vergeten', [PasswordResetLinkController::class, 'create'])->middleware('guest')->name('password.request');
Route::post('/wachtwoord-vergeten', [PasswordResetLinkController::class, 'store'])->middleware('guest')->name('password.email');
Route::get('/wachtwoord-herstellen/{token}', [NewPasswordController::class, 'create'])->middleware('guest')->name('password.reset');
Route::post('/wachtwoord-herstellen', [NewPasswordController::class, 'store'])->middleware('guest')->name('password.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/email/verificatie', [EmailVerificationController::class, 'notice'])->middleware('auth')->name('verification.notice');
Route::get('/email/verificatie/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verificatie-link', [EmailVerificationController::class, 'send'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::get('/setup', [SetupController::class, 'create'])->middleware('throttle:setup')->name('setup');
Route::post('/setup', [SetupController::class, 'store'])->middleware('throttle:setup')->name('setup.store');

Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function (): void {
    Route::get('/', [EntertainerDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/profiel', [EntertainerDashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::post('/profiel/publicatie-aanvragen', [EntertainerDashboardController::class, 'requestPublication'])->name('dashboard.profile.request-publication');
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

Route::middleware(['auth', 'verified'])->prefix('klantportaal')->name('customer-portal.')->group(function (): void {
    Route::get('/', [CustomerPortalController::class, 'index'])->name('index');
    Route::get('/aanvragen/{bookingRequest}', [CustomerPortalController::class, 'show'])->name('show');
    Route::patch('/aanvragen/{bookingRequest}', [CustomerPortalController::class, 'update'])->name('update');
    Route::post('/aanvragen/{bookingRequest}/berichten', [CustomerPortalController::class, 'storeMessage'])->name('messages.store');
    Route::post('/aanvragen/{bookingRequest}/annuleren', [CustomerPortalController::class, 'cancel'])->name('cancel');
    Route::post('/aanvragen/{bookingRequest}/offerte-accepteren', [CustomerPortalController::class, 'acceptQuote'])->name('accept-quote');
    Route::get('/aanvragen/{bookingRequest}/document/{type?}', [CustomerPortalController::class, 'download'])->name('download');
    Route::post('/favorieten/{entertainer}', [CustomerPortalController::class, 'favorite'])->name('favorites.store');
    Route::delete('/favorieten/{entertainer}', [CustomerPortalController::class, 'unfavorite'])->name('favorites.destroy');
});

Route::get('/{landingPage:slug}', [LandingPageController::class, 'show'])
    ->where('landingPage', '^(?!admin$|dashboard$|klantportaal$|kinderentertainers$|aanvragen$|aanvraag-bedankt$|reviews$|review-bedankt$|algemene-voorwaarden$|privacyverklaring$|cookieverklaring$|offertes$|login$|registreren$|logout$|setup$|sitemap\.xml$|webhooks$|email$|wachtwoord-vergeten$|wachtwoord-herstellen$)[a-z0-9-]+$')
    ->name('landing-pages.show');
