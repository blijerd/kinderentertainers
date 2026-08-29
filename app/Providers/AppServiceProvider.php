<?php

namespace App\Providers;

use App\Actions\ResolveApplicationBuildReferenceAction;
use App\Models\Availability;
use App\Models\BlogPost;
use App\Models\BookingRequest;
use App\Models\ContentMedia;
use App\Models\Entertainer;
use App\Models\LandingPage;
use App\Models\Rate;
use App\Models\Skill;
use App\Policies\AvailabilityPolicy;
use App\Policies\BlogPostPolicy;
use App\Policies\BookingRequestPolicy;
use App\Policies\ContentMediaPolicy;
use App\Policies\EntertainerPolicy;
use App\Policies\LandingPagePolicy;
use App\Policies\RatePolicy;
use App\Policies\SkillPolicy;
use Filament\Support\Enums\Width;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
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
        require_once base_path('bootstrap/compiled_views.php');

        $compiledViewsPath = (string) config('view.compiled');

        if ($compiledViewsPath !== '' && ! is_dir($compiledViewsPath)) {
            mkdir($compiledViewsPath, 0775, true);
        }
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
        Gate::policy(LandingPage::class, LandingPagePolicy::class);
        Gate::policy(BlogPost::class, BlogPostPolicy::class);
        Gate::policy(ContentMedia::class, ContentMediaPolicy::class);

        Model::preventLazyLoading($this->app->isLocal());

        View::share('company', config('company'));

        $this->configureFilamentTables();

        View::composer([
            'components.layouts.app',
            'partials.footer-build-ref',
        ], function ($view): void {
            $resolver = app(ResolveApplicationBuildReferenceAction::class);
            $buildReference = $resolver->handle();

            $view->with('buildReference', $buildReference);
            $view->with('buildReferenceDisplay', $resolver->displayLabel($buildReference));
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('setup', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('public-booking-requests', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('payment-webhook', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        $appUrl = (string) config('app.url');

        if ($this->app->environment('production') && str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme('https');
        }
    }

    protected function configureFilamentTables(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->stackedOnMobile()
                ->filtersLayout(FiltersLayout::AboveContentCollapsible)
                ->filtersFormColumns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                ->filtersFormWidth(Width::FiveExtraLarge)
                ->persistFiltersInSession()
                ->persistSearchInSession()
                ->persistColumnSearchesInSession()
                ->paginationPageOptions([10, 25, 50, 100])
                ->defaultPaginationPageOption(25);
        });
    }
}
