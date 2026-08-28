<?php

namespace App\Providers;

use App\Models\Availability;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use App\Models\Rate;
use App\Models\Skill;
use App\Policies\AvailabilityPolicy;
use App\Policies\BookingRequestPolicy;
use App\Policies\EntertainerPolicy;
use App\Policies\RatePolicy;
use App\Policies\SkillPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Entertainer::class, EntertainerPolicy::class);
        Gate::policy(Availability::class, AvailabilityPolicy::class);
        Gate::policy(Rate::class, RatePolicy::class);
        Gate::policy(BookingRequest::class, BookingRequestPolicy::class);
        Gate::policy(Skill::class, SkillPolicy::class);

        View::share('company', config('company'));

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('setup', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
