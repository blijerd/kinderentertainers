<?php

namespace App\Http\Controllers;

use App\Actions\CreateAvailability;
use App\Actions\CreateAvailabilityRule;
use App\Actions\CreateBookingQuote;
use App\Actions\CreateRate;
use App\Actions\DeleteAvailability;
use App\Actions\DeleteAvailabilityRule;
use App\Actions\DeleteRate;
use App\Actions\EnsureDefaultEntertainerIntegrations;
use App\Actions\RecalculateEntertainerProfileQuality;
use App\Actions\RecordBookingRequestEvent;
use App\Actions\RequestEntertainerPublication;
use App\Actions\RespondToBookingRequestMatch;
use App\Actions\SyncEntertainerSkills;
use App\Actions\TransitionBookingRequestStatus;
use App\Actions\UpdateAvailability;
use App\Actions\UpdateAvailabilityRule;
use App\Actions\UpdateEntertainerBilling;
use App\Actions\UpdateEntertainerIntegration;
use App\Actions\UpdateEntertainerProfile;
use App\Actions\UpdateRate;
use App\Enums\AccountingProvider;
use App\Enums\AvailabilityStatus;
use App\Enums\BookingRequestEventType;
use App\Enums\BookingRequestMatchStatus;
use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use App\Enums\IntegrationProvider;
use App\Enums\PaymentProvider;
use App\Models\Availability;
use App\Models\AvailabilityRule;
use App\Models\BookingRequest;
use App\Models\BookingRequestMatch;
use App\Models\Entertainer;
use App\Models\EntertainerIntegration;
use App\Models\Rate;
use App\Models\Skill;
use App\Services\ProfileQualityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EntertainerDashboardController extends Controller
{
    public function index(
        Request $request,
        ProfileQualityService $profileQualityService,
        EnsureDefaultEntertainerIntegrations $ensureDefaultIntegrations,
        RecalculateEntertainerProfileQuality $recalculateProfileQuality,
    ): View {
        $entertainer = $this->currentEntertainer($request);

        $ensureDefaultIntegrations->handle($entertainer);

        $entertainer->load([
            'skills',
            'rates',
            'integrations' => fn ($query) => $query->orderBy('provider'),
            'availabilities' => fn ($query) => $query->upcoming()->orderBy('date'),
            'availabilityRules' => fn ($query) => $query->orderBy('starts_on')->orderBy('name'),
        ]);
        $bookingRequests = $entertainer->bookingRequests()
            ->with(['events' => fn ($query) => $query->where('visible_to_entertainer', true)->oldest()])
            ->latest()
            ->paginate(10, ['*'], 'aanvragen');
        $bookingRequestMatches = $entertainer->bookingRequestMatches()
            ->with([
                'bookingRequest.skill',
                'bookingRequest.events' => fn ($query) => $query->where('visible_to_entertainer', true)->oldest(),
            ])
            ->latest()
            ->paginate(10, ['*'], 'matches');
        $skills = Skill::where('active', true)->orderBy('name')->get();
        $profileQualityScore = $recalculateProfileQuality->handle($entertainer);
        $profileMissingItems = $profileQualityService->missingItems($entertainer->refresh());

        return view('dashboard.index', compact(
            'entertainer',
            'bookingRequests',
            'bookingRequestMatches',
            'skills',
            'profileQualityScore',
            'profileMissingItems',
        ));
    }

    public function updateProfile(Request $request, UpdateEntertainerProfile $updateEntertainerProfile): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        if (! $request->has('deposit_percentage')) {
            $request->merge(['deposit_percentage' => $entertainer->deposit_percentage ?? 0]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_introduction' => ['required', 'string', 'max:240'],
            'bio' => ['nullable', 'string', 'max:10000'],
            'profile_highlights' => ['nullable', 'string', 'max:2000'],
            'audience_age_range' => ['nullable', 'string', 'max:255'],
            'event_types' => ['nullable', 'string', 'max:1000'],
            'languages' => ['nullable', 'string', 'max:1000'],
            'performance_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'setup_time_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'show_reel_url' => ['nullable', 'url', 'max:255'],
            'practical_requirements' => ['nullable', 'string', 'max:5000'],
            'packages' => ['nullable', 'string', 'max:5000'],
            'extras' => ['nullable', 'string', 'max:5000'],
            'cancellation_policy' => ['nullable', 'string', 'max:5000'],
            'deposit_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'working_radius_km' => ['required', 'integer', 'min:1', 'max:500'],
            'travel_free_km' => ['required', 'integer', 'min:0', 'max:500'],
            'max_travel_distance_km' => ['nullable', 'integer', 'min:1', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'gallery_photos' => ['nullable', 'array', 'max:12'],
            'gallery_photos.*' => ['image', 'max:5120'],
            'remove_gallery_photos' => ['nullable', 'array'],
            'remove_gallery_photos.*' => ['string'],
        ], attributes: [
            'profile_highlights' => 'profielhighlights',
            'audience_age_range' => 'leeftijdsrange',
            'event_types' => 'feesttypes',
            'languages' => 'talen',
            'performance_duration_minutes' => 'duur van optreden',
            'setup_time_minutes' => 'opbouwtijd',
            'show_reel_url' => 'showreel URL',
            'practical_requirements' => 'praktische eisen',
            'cancellation_policy' => 'annuleringsvoorwaarden',
            'deposit_percentage' => 'aanbetalingspercentage',
            'travel_free_km' => 'vrije reiskilometers',
            'max_travel_distance_km' => 'maximale reisafstand',
            'profile_photo' => 'profielfoto',
            'gallery_photos' => 'galerijfoto\'s',
        ]);

        $updateEntertainerProfile->handle($entertainer, $validated, $request);

        return back()->with('status', 'Profiel bijgewerkt.');
    }

    public function requestPublication(Request $request, RequestEntertainerPublication $requestEntertainerPublication): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        $requestEntertainerPublication->handle($entertainer);

        return back()->with('status', 'Publicatie aangevraagd. Een beheerder controleert je profiel.');
    }

    public function updateBilling(Request $request, UpdateEntertainerBilling $updateEntertainerBilling): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        $validated = $request->validate([
            'accounting_provider' => ['required', Rule::enum(AccountingProvider::class)],
            'accounting_notes' => ['nullable', 'string', 'max:5000'],
            'payment_provider' => ['required', Rule::enum(PaymentProvider::class)],
            'cash_payment_enabled' => ['nullable', 'boolean'],
            'payment_notes' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'accounting_provider' => 'boekhoudpakket',
            'accounting_notes' => 'facturatienotities',
            'payment_provider' => 'betaalprovider',
            'cash_payment_enabled' => 'contant betalen',
            'payment_notes' => 'betaalnotities',
        ]);

        $validated['cash_payment_enabled'] = $request->boolean('cash_payment_enabled');

        $updateEntertainerBilling->handle($entertainer, $validated);

        return back()->with('status', 'Facturatie-instellingen bijgewerkt.');
    }

    public function updateIntegration(Request $request, EntertainerIntegration $integration, UpdateEntertainerIntegration $updateEntertainerIntegration): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        abort_unless($integration->entertainer_id === $entertainer->id, 403);
        $this->authorize('update', $entertainer);

        $updateEntertainerIntegration->handle($integration, $this->validateIntegration($request, $integration));

        return back()->with('status', $integration->provider->label().' bijgewerkt.');
    }

    public function updateSkills(Request $request, SyncEntertainerSkills $syncEntertainerSkills): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        $validated = $request->validate([
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', Rule::exists('skills', 'id')->where('active', true)],
        ]);

        $syncEntertainerSkills->handle($entertainer, $validated['skills'] ?? []);

        return back()->with('status', 'Skills bijgewerkt.');
    }

    public function storeAvailability(Request $request, CreateAvailability $createAvailability): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('create', Availability::class);

        $createAvailability->handle($entertainer, $this->validateAvailability($request));

        return back()->with('status', 'Beschikbaarheid toegevoegd.');
    }

    public function updateAvailability(Request $request, Availability $availability, UpdateAvailability $updateAvailability): RedirectResponse
    {
        $this->authorize('update', $availability);

        $updateAvailability->handle($availability, $this->validateAvailability($request));

        return back()->with('status', 'Beschikbaarheid bijgewerkt.');
    }

    public function destroyAvailability(Availability $availability, DeleteAvailability $deleteAvailability): RedirectResponse
    {
        $this->authorize('delete', $availability);

        $deleteAvailability->handle($availability);

        return back()->with('status', 'Beschikbaarheid verwijderd.');
    }

    public function storeAvailabilityRule(Request $request, CreateAvailabilityRule $createAvailabilityRule): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        $createAvailabilityRule->handle($entertainer, $this->validateAvailabilityRule($request));

        return back()->with('status', 'Herhalende beschikbaarheid toegevoegd.');
    }

    public function updateAvailabilityRule(Request $request, AvailabilityRule $availabilityRule, UpdateAvailabilityRule $updateAvailabilityRule): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        abort_unless($availabilityRule->entertainer_id === $entertainer->id, 403);
        $this->authorize('update', $entertainer);

        $updateAvailabilityRule->handle($availabilityRule, $this->validateAvailabilityRule($request));

        return back()->with('status', 'Herhalende beschikbaarheid bijgewerkt.');
    }

    public function destroyAvailabilityRule(Request $request, AvailabilityRule $availabilityRule, DeleteAvailabilityRule $deleteAvailabilityRule): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        abort_unless($availabilityRule->entertainer_id === $entertainer->id, 403);
        $this->authorize('update', $entertainer);

        $deleteAvailabilityRule->handle($availabilityRule);

        return back()->with('status', 'Herhalende beschikbaarheid verwijderd.');
    }

    public function storeRate(Request $request, CreateRate $createRate): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('create', Rate::class);

        $createRate->handle($entertainer, $this->validateRate($request, $entertainer));

        return back()->with('status', 'Tarief toegevoegd.');
    }

    public function updateRate(Request $request, Rate $rate, UpdateRate $updateRate): RedirectResponse
    {
        $this->authorize('update', $rate);

        $updateRate->handle($rate, $this->validateRate($request, $rate->entertainer, $rate));

        return back()->with('status', 'Tarief bijgewerkt.');
    }

    public function destroyRate(Rate $rate, DeleteRate $deleteRate): RedirectResponse
    {
        $this->authorize('delete', $rate);

        $deleteRate->handle($rate);

        return back()->with('status', 'Tarief verwijderd.');
    }

    public function updateBookingStatus(Request $request, BookingRequest $bookingRequest, TransitionBookingRequestStatus $transition): RedirectResponse
    {
        $this->authorize('update', $bookingRequest);

        $validated = $request->validate([
            'status' => ['required', 'in:optie,bevestigd,afgewezen,geannuleerd'],
            'cancellation_reason' => ['nullable', 'required_if:status,geannuleerd', 'string', 'max:5000'],
        ]);

        $transition->handle(
            $bookingRequest,
            BookingStatus::from($validated['status']),
            $validated['cancellation_reason'] ?? null,
            'entertainer',
        );

        return back()->with('status', 'Aanvraagstatus bijgewerkt.');
    }

    public function storeBookingRequestEvent(Request $request, BookingRequest $bookingRequest, RecordBookingRequestEvent $recordBookingRequestEvent): RedirectResponse
    {
        $this->authorize('update', $bookingRequest);

        $validated = $request->validate([
            'type' => ['required', Rule::in([
                BookingRequestEventType::EntertainerResponse->value,
                BookingRequestEventType::InternalNote->value,
            ])],
            'body' => ['required', 'string', 'max:5000'],
        ], attributes: [
            'body' => 'bericht',
            'type' => 'type logregel',
        ]);

        $eventType = BookingRequestEventType::from($validated['type']);

        $recordBookingRequestEvent->handle(
            $bookingRequest,
            $eventType,
            $validated['body'],
            'entertainer',
            $request->user()->name,
            true,
            $eventType === BookingRequestEventType::EntertainerResponse,
            $request->user(),
        );

        return back()->with('status', $eventType === BookingRequestEventType::InternalNote ? 'Notitie toegevoegd.' : 'Reactie toegevoegd.');
    }

    public function createBookingQuote(Request $request, BookingRequest $bookingRequest, CreateBookingQuote $createBookingQuote): RedirectResponse
    {
        $this->authorize('update', $bookingRequest);

        $validated = $request->validate([
            'travel_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.9'],
            'valid_days' => ['required', 'integer', 'min:1', 'max:60'],
        ], attributes: [
            'travel_distance_km' => 'reisafstand',
            'valid_days' => 'geldigheid',
        ]);

        try {
            $createBookingQuote->handle(
                $bookingRequest,
                (float) ($validated['travel_distance_km'] ?? 0),
                (int) $validated['valid_days'],
            );
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages([
                'travel_distance_km' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Offerte aangemaakt. Deel de akkoordlink met de klant.');
    }

    public function updateMatchResponse(Request $request, BookingRequestMatch $match, RespondToBookingRequestMatch $respondToBookingRequestMatch): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        abort_unless($match->entertainer_id === $entertainer->id, 403);
        $this->authorize('view', $match->bookingRequest);

        $validated = $request->validate([
            'response' => ['required', Rule::in(['available', 'accepted', 'rejected'])],
            'price_indication_euros' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'response_message' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'price_indication_euros' => 'prijsindicatie',
            'response_message' => 'bericht',
        ]);

        if ($match->status === BookingRequestMatchStatus::Accepted) {
            return back()->with('status', 'Deze match is al gekozen.');
        }

        $respondToBookingRequestMatch->handle($match, $entertainer, $validated, $request->user());

        return back()->with('status', 'Reactie opgeslagen.');
    }

    private function currentEntertainer(Request $request): Entertainer
    {
        $entertainer = $request->user()->entertainer;

        abort_unless($entertainer, 403);

        return $entertainer;
    }

    private function validateIntegration(Request $request, EntertainerIntegration $integration): array
    {
        $base = [
            'enabled' => $request->boolean('enabled'),
            'credentials' => $integration->credentials ?? [],
            'settings' => $integration->settings ?? [],
        ];

        $rules = match ($integration->provider) {
            IntegrationProvider::Moneybird => [
                'api_token' => ['nullable', 'string', 'max:5000'],
                'administration_id' => ['nullable', 'string', 'max:255'],
                'workflow_id' => ['nullable', 'string', 'max:255'],
            ],
            IntegrationProvider::Exact,
            IntegrationProvider::SnelStart,
            IntegrationProvider::Twinfield,
            IntegrationProvider::Visma => [
                'client_id' => ['nullable', 'string', 'max:5000'],
                'client_secret' => ['nullable', 'string', 'max:5000'],
                'refresh_token' => ['nullable', 'string', 'max:5000'],
                'administration_id' => ['nullable', 'string', 'max:255'],
            ],
            IntegrationProvider::EBoekhouden => [
                'username' => ['nullable', 'string', 'max:255'],
                'security_code_1' => ['nullable', 'string', 'max:5000'],
                'security_code_2' => ['nullable', 'string', 'max:5000'],
            ],
            IntegrationProvider::Jortt,
            IntegrationProvider::Rompslomp => [
                'api_token' => ['nullable', 'string', 'max:5000'],
                'administration_id' => ['nullable', 'string', 'max:255'],
            ],
            IntegrationProvider::Mollie => [
                'api_key' => ['nullable', 'string', 'max:5000'],
                'profile_id' => ['nullable', 'string', 'max:255'],
            ],
            IntegrationProvider::Stripe => [
                'secret_key' => ['nullable', 'string', 'max:5000'],
                'webhook_secret' => ['nullable', 'string', 'max:5000'],
            ],
            IntegrationProvider::PayPal => [
                'client_id' => ['nullable', 'string', 'max:5000'],
                'client_secret' => ['nullable', 'string', 'max:5000'],
                'merchant_id' => ['nullable', 'string', 'max:255'],
            ],
            IntegrationProvider::PayNl => [
                'api_token' => ['nullable', 'string', 'max:5000'],
                'service_id' => ['nullable', 'string', 'max:255'],
            ],
            IntegrationProvider::Rabobank => [
                'refresh_token' => ['nullable', 'string', 'max:5000'],
                'merchant_id' => ['nullable', 'string', 'max:255'],
            ],
            IntegrationProvider::Postmark => [
                'server_token' => ['nullable', 'string', 'max:5000'],
                'message_stream' => ['nullable', 'string', 'max:255'],
                'from_email' => ['nullable', 'email', 'max:255'],
            ],
            IntegrationProvider::Pushover => [
                'app_token' => ['nullable', 'string', 'max:5000'],
                'user_key' => ['nullable', 'string', 'max:5000'],
            ],
            IntegrationProvider::GoogleCalendar => [
                'client_id' => ['nullable', 'string', 'max:5000'],
                'client_secret' => ['nullable', 'string', 'max:5000'],
                'refresh_token' => ['nullable', 'string', 'max:5000'],
                'calendar_id' => ['nullable', 'string', 'max:255'],
                'sync_direction' => ['required', 'in:read_only,two_way'],
                'block_busy_events' => ['nullable', 'boolean'],
            ],
            IntegrationProvider::OutlookCalendar => [
                'tenant_id' => ['nullable', 'string', 'max:255'],
                'client_id' => ['nullable', 'string', 'max:5000'],
                'client_secret' => ['nullable', 'string', 'max:5000'],
                'refresh_token' => ['nullable', 'string', 'max:5000'],
                'calendar_id' => ['nullable', 'string', 'max:255'],
                'sync_direction' => ['required', 'in:read_only,two_way'],
                'block_busy_events' => ['nullable', 'boolean'],
            ],
        };

        $validated = $request->validate($rules, attributes: [
            'api_token' => 'API-token',
            'administration_id' => 'administratie-ID',
            'workflow_id' => 'workflow-ID',
            'api_key' => 'API-key',
            'profile_id' => 'profiel-ID',
            'server_token' => 'server-token',
            'message_stream' => 'message stream',
            'from_email' => 'afzender',
            'app_token' => 'app-token',
            'user_key' => 'user-key',
            'client_id' => 'client-ID',
            'client_secret' => 'client secret',
            'refresh_token' => 'refresh token',
            'tenant_id' => 'tenant-ID',
            'calendar_id' => 'agenda-ID',
            'username' => 'gebruikersnaam',
            'security_code_1' => 'beveiligingscode 1',
            'security_code_2' => 'beveiligingscode 2',
            'secret_key' => 'secret key',
            'webhook_secret' => 'webhook secret',
            'merchant_id' => 'merchant-ID',
            'service_id' => 'service-ID',
            'sync_direction' => 'synchronisatierichting',
            'block_busy_events' => 'bezet blokkeren',
        ]);

        if (array_key_exists('block_busy_events', $rules)) {
            $validated['block_busy_events'] = $request->boolean('block_busy_events');
        }

        foreach ($validated as $key => $value) {
            if (! is_bool($value) && blank($value)) {
                continue;
            }

            if (
                str_contains($key, 'token')
                || str_contains($key, 'key')
                || str_contains($key, 'secret')
                || str_contains($key, 'security_code')
            ) {
                $base['credentials'][$key] = $value;
            } else {
                $base['settings'][$key] = $value;
            }
        }

        return $base;
    }

    private function validateAvailability(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::enum(AvailabilityStatus::class)],
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'date' => 'datum',
            'start_time' => 'starttijd',
            'end_time' => 'eindtijd',
            'internal_note' => 'interne notitie',
        ]);
    }

    private function validateAvailabilityRule(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rule_type' => ['required', 'in:weekly,date_range'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::enum(AvailabilityStatus::class)],
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'name' => 'naam',
            'rule_type' => 'herhalingstype',
            'weekdays' => 'weekdagen',
            'starts_on' => 'startdatum',
            'ends_on' => 'einddatum',
            'start_time' => 'starttijd',
            'end_time' => 'eindtijd',
            'internal_note' => 'interne notitie',
        ]);

        $validated['weekdays'] = $validated['rule_type'] === 'weekly'
            ? array_values(array_map('intval', $validated['weekdays'] ?? []))
            : null;

        if ($validated['rule_type'] === 'weekly' && $validated['weekdays'] === []) {
            throw ValidationException::withMessages([
                'weekdays' => 'Kies minimaal een weekdag.',
            ]);
        }

        return $validated;
    }

    private function validateRate(Request $request, Entertainer $entertainer, ?Rate $rate = null): array
    {
        if (! $request->has('starting_rate_euros') && $request->has('starting_rate_cents')) {
            $request->merge([
                'starting_rate_euros' => ((float) $request->input('starting_rate_cents')) / 100,
                'hourly_rate_euros' => ((float) $request->input('hourly_rate_cents')) / 100,
                'travel_cost_euros_per_km' => ((float) $request->input('travel_cost_cents_per_km')) / 100,
            ]);
        }

        $validated = $request->validate([
            'customer_type' => [
                'required',
                Rule::enum(CustomerType::class),
                Rule::unique('rates', 'customer_type')
                    ->where('entertainer_id', $entertainer->id)
                    ->ignore($rate)
                    ->withoutTrashed(),
            ],
            'starting_rate_euros' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'hourly_rate_euros' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'minimum_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'travel_cost_euros_per_km' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'vat_included' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'customer_type' => 'doelgroep',
            'starting_rate_cents' => 'starttarief',
            'hourly_rate_cents' => 'uurtarief',
            'starting_rate_euros' => 'starttarief',
            'hourly_rate_euros' => 'uurtarief',
            'minimum_hours' => 'minimum aantal uren',
            'travel_cost_cents_per_km' => 'reiskosten per kilometer',
            'travel_cost_euros_per_km' => 'reiskosten per kilometer',
            'vat_included' => 'btw inclusief',
        ]);

        $validated['starting_rate_cents'] = (int) round(((float) $validated['starting_rate_euros']) * 100);
        $validated['hourly_rate_cents'] = (int) round(((float) $validated['hourly_rate_euros']) * 100);
        $validated['travel_cost_cents_per_km'] = (int) round(((float) $validated['travel_cost_euros_per_km']) * 100);
        $validated['vat_included'] = $request->boolean('vat_included');

        unset($validated['starting_rate_euros'], $validated['hourly_rate_euros'], $validated['travel_cost_euros_per_km']);

        return $validated;
    }
}
