<?php

namespace App\Http\Controllers;

use App\Actions\CreateBookingQuote;
use App\Enums\AccountingProvider;
use App\Enums\AvailabilityStatus;
use App\Enums\BookingRequestEventType;
use App\Enums\BookingRequestMatchStatus;
use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use App\Enums\IntegrationProvider;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EntertainerDashboardController extends Controller
{
    public function index(Request $request, ProfileQualityService $profileQualityService): View
    {
        $entertainer = $this->currentEntertainer($request);

        $this->ensureDefaultIntegrations($entertainer);

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
        $profileQualityScore = $profileQualityService->score($entertainer);
        $profileMissingItems = $profileQualityService->missingItems($entertainer);

        if ($entertainer->profile_quality_score !== $profileQualityScore) {
            $entertainer->forceFill(['profile_quality_score' => $profileQualityScore])->save();
        }

        return view('dashboard.index', compact(
            'entertainer',
            'bookingRequests',
            'bookingRequestMatches',
            'skills',
            'profileQualityScore',
            'profileMissingItems',
        ));
    }

    public function updateProfile(Request $request): RedirectResponse
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
            'profile_photo' => 'profielfoto',
            'gallery_photos' => 'galerijfoto\'s',
        ]);

        $validated['profile_highlights'] = collect(preg_split('/\r\n|\r|\n/', $validated['profile_highlights'] ?? ''))
            ->map(fn (string $highlight): string => trim($highlight))
            ->filter()
            ->values()
            ->all();
        $validated['event_types'] = $this->linesToArray($validated['event_types'] ?? '');
        $validated['languages'] = $this->linesToArray($validated['languages'] ?? '');
        $validated['packages'] = $this->linesToOfferItems($validated['packages'] ?? '');
        $validated['extras'] = $this->linesToOfferItems($validated['extras'] ?? '');

        unset($validated['profile_photo'], $validated['gallery_photos'], $validated['remove_gallery_photos']);

        $galleryPhotoPaths = collect($entertainer->gallery_photo_paths ?? []);
        $removedGalleryPhotoPaths = collect($request->input('remove_gallery_photos', []))
            ->filter()
            ->intersect($galleryPhotoPaths)
            ->values();
        $newGalleryPhotos = $request->file('gallery_photos', []);

        if ($galleryPhotoPaths->count() - $removedGalleryPhotoPaths->count() + count($newGalleryPhotos) > 12) {
            throw ValidationException::withMessages([
                'gallery_photos' => 'Je kunt maximaal 12 galerijfoto\'s bewaren.',
            ]);
        }

        if ($request->hasFile('profile_photo')) {
            if ($entertainer->profile_photo_path) {
                Storage::disk('public')->delete($entertainer->profile_photo_path);
            }

            $validated['profile_photo_path'] = $request->file('profile_photo')->store('entertainers/profile-photos', 'public');
        }

        if ($removedGalleryPhotoPaths->isNotEmpty()) {
            Storage::disk('public')->delete($removedGalleryPhotoPaths->all());
            $galleryPhotoPaths = $galleryPhotoPaths->reject(fn (string $path): bool => $removedGalleryPhotoPaths->contains($path));
        }

        foreach ($newGalleryPhotos as $galleryPhoto) {
            $galleryPhotoPaths->push($galleryPhoto->store('entertainers/gallery', 'public'));
        }

        $validated['gallery_photo_paths'] = $galleryPhotoPaths->values()->all();

        $entertainer->update($validated);

        return back()->with('status', 'Profiel bijgewerkt.');
    }

    private function linesToArray(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function linesToOfferItems(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(function (string $line): array {
                [$name, $price, $description] = array_pad(array_map('trim', explode('|', $line, 3)), 3, null);

                return [
                    'name' => $name,
                    'price_cents' => filled($price) ? (int) round(((float) str_replace(',', '.', $price)) * 100) : null,
                    'description' => $description,
                ];
            })
            ->values()
            ->all();
    }

    public function updateBilling(Request $request): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        $validated = $request->validate([
            'accounting_provider' => ['required', Rule::enum(AccountingProvider::class)],
            'accounting_notes' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'accounting_provider' => 'boekhoudpakket',
            'accounting_notes' => 'facturatienotities',
        ]);

        $entertainer->update($validated);

        return back()->with('status', 'Facturatie-instellingen bijgewerkt.');
    }

    public function updateIntegration(Request $request, EntertainerIntegration $integration): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        abort_unless($integration->entertainer_id === $entertainer->id, 403);
        $this->authorize('update', $entertainer);

        $validated = $this->validateIntegration($request, $integration);

        $integration->update($validated);

        return back()->with('status', $integration->provider->label().' bijgewerkt.');
    }

    public function updateSkills(Request $request): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        $validated = $request->validate([
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', Rule::exists('skills', 'id')->where('active', true)],
        ]);

        $entertainer->skills()->sync($validated['skills'] ?? []);

        return back()->with('status', 'Skills bijgewerkt.');
    }

    public function storeAvailability(Request $request): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('create', Availability::class);

        $validated = $this->validateAvailability($request);
        $entertainer->availabilities()->create($validated);

        return back()->with('status', 'Beschikbaarheid toegevoegd.');
    }

    public function updateAvailability(Request $request, Availability $availability): RedirectResponse
    {
        $this->authorize('update', $availability);

        $availability->update($this->validateAvailability($request));

        return back()->with('status', 'Beschikbaarheid bijgewerkt.');
    }

    public function destroyAvailability(Availability $availability): RedirectResponse
    {
        $this->authorize('delete', $availability);

        $availability->delete();

        return back()->with('status', 'Beschikbaarheid verwijderd.');
    }

    public function storeAvailabilityRule(Request $request): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        $entertainer->availabilityRules()->create($this->validateAvailabilityRule($request));

        return back()->with('status', 'Herhalende beschikbaarheid toegevoegd.');
    }

    public function updateAvailabilityRule(Request $request, AvailabilityRule $availabilityRule): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        abort_unless($availabilityRule->entertainer_id === $entertainer->id, 403);
        $this->authorize('update', $entertainer);

        $availabilityRule->update($this->validateAvailabilityRule($request));

        return back()->with('status', 'Herhalende beschikbaarheid bijgewerkt.');
    }

    public function destroyAvailabilityRule(Request $request, AvailabilityRule $availabilityRule): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        abort_unless($availabilityRule->entertainer_id === $entertainer->id, 403);
        $this->authorize('update', $entertainer);

        $availabilityRule->delete();

        return back()->with('status', 'Herhalende beschikbaarheid verwijderd.');
    }

    public function storeRate(Request $request): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('create', Rate::class);

        $validated = $this->validateRate($request, $entertainer);
        $entertainer->rates()->create($validated);

        return back()->with('status', 'Tarief toegevoegd.');
    }

    public function updateRate(Request $request, Rate $rate): RedirectResponse
    {
        $this->authorize('update', $rate);

        $rate->update($this->validateRate($request, $rate->entertainer, $rate));

        return back()->with('status', 'Tarief bijgewerkt.');
    }

    public function destroyRate(Rate $rate): RedirectResponse
    {
        $this->authorize('delete', $rate);

        $rate->delete();

        return back()->with('status', 'Tarief verwijderd.');
    }

    public function updateBookingStatus(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $this->authorize('update', $bookingRequest);

        $validated = $request->validate([
            'status' => ['required', 'in:optie,bevestigd,afgewezen,geannuleerd'],
            'cancellation_reason' => ['nullable', 'required_if:status,geannuleerd', 'string', 'max:5000'],
        ]);

        $data = ['status' => BookingStatus::from($validated['status'])];

        if ($data['status'] === BookingStatus::Cancelled) {
            $data += [
                'cancelled_at' => now(),
                'cancelled_by' => 'entertainer',
                'cancellation_reason' => $validated['cancellation_reason'] ?? null,
            ];
        }

        $bookingRequest->update($data);

        return back()->with('status', 'Aanvraagstatus bijgewerkt.');
    }

    public function storeBookingRequestEvent(Request $request, BookingRequest $bookingRequest): RedirectResponse
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

        $bookingRequest->events()->create([
            'type' => $eventType,
            'actor_type' => 'entertainer',
            'actor_name' => $request->user()->name,
            'body' => $validated['body'],
            'visible_to_entertainer' => true,
            'visible_to_customer' => $eventType === BookingRequestEventType::EntertainerResponse,
            'user_id' => $request->user()->id,
        ]);

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

    public function updateMatchResponse(Request $request, BookingRequestMatch $match): RedirectResponse
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

        $match->update([
            'status' => match ($validated['response']) {
                'accepted' => BookingRequestMatchStatus::Accepted,
                'available' => BookingRequestMatchStatus::Available,
                default => BookingRequestMatchStatus::Rejected,
            },
            'price_indication_cents' => in_array($validated['response'], ['available', 'accepted'], true) && filled($validated['price_indication_euros'] ?? null)
                ? (int) round(((float) $validated['price_indication_euros']) * 100)
                : null,
            'response_message' => $validated['response_message'] ?? null,
            'responded_at' => now(),
        ]);

        if (filled($validated['response_message'] ?? null)) {
            $match->bookingRequest->events()->create([
                'type' => BookingRequestEventType::EntertainerResponse,
                'actor_type' => 'entertainer',
                'actor_name' => $entertainer->name,
                'body' => $validated['response_message'],
                'visible_to_entertainer' => true,
                'visible_to_customer' => true,
                'user_id' => $request->user()->id,
            ]);
        }

        if ($validated['response'] === 'accepted') {
            $match->bookingRequest()->update(['status' => BookingStatus::Option]);
        }

        return back()->with('status', 'Reactie opgeslagen.');
    }

    private function currentEntertainer(Request $request): Entertainer
    {
        $entertainer = $request->user()->entertainer;

        abort_unless($entertainer, 403);

        return $entertainer;
    }

    private function ensureDefaultIntegrations(Entertainer $entertainer): void
    {
        foreach (IntegrationProvider::cases() as $provider) {
            $entertainer->integrations()->firstOrCreate(['provider' => $provider]);
        }
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
            IntegrationProvider::Mollie => [
                'api_key' => ['nullable', 'string', 'max:5000'],
                'profile_id' => ['nullable', 'string', 'max:255'],
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

            if (str_contains($key, 'token') || str_contains($key, 'key') || $key === 'client_secret') {
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
                    ->ignore($rate),
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
