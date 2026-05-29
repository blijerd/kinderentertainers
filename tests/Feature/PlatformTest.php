<?php

namespace Tests\Feature;

use App\Actions\CheckEntertainerAvailability;
use App\Actions\CreateBookingQuote;
use App\Actions\FindAvailableEntertainersForRequest;
use App\Enums\AccountingProvider;
use App\Enums\AvailabilityStatus;
use App\Enums\BookingRequestEventType;
use App\Enums\BookingRequestMatchStatus;
use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use App\Enums\IntegrationProvider;
use App\Enums\LegalDocumentType;
use App\Enums\PaymentProvider;
use App\Enums\ReviewStatus;
use App\Mail\ReviewRequestMail;
use App\Models\Availability;
use App\Models\AvailabilityRule;
use App\Models\BookingRequest;
use App\Models\BookingRequestMatch;
use App\Models\Entertainer;
use App\Models\EntertainerIntegration;
use App\Models\LandingPage;
use App\Models\LegalDocument;
use App\Models\Rate;
use App\Models\Review;
use App\Models\Skill;
use App\Models\User;
use App\Services\AdminDashboardSignalService;
use App\Services\Integrations\InvoiceIntegrationService;
use App\Services\Integrations\PaymentCheckoutService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_entertainer_index_works(): void
    {
        Entertainer::factory()->create(['name' => 'Sanne Schminkster', 'active' => true]);

        $this->get(route('entertainers.index'))
            ->assertOk()
            ->assertSee('Kinderentertainers zoeken')
            ->assertSee('Sanne Schminkster');
    }

    public function test_skill_filter_works(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $goochelaar = Skill::factory()->create(['name' => 'Goochelaar', 'slug' => 'goochelaar']);
        $match = Entertainer::factory()->create(['name' => 'Sanne Schminkster', 'active' => true]);
        $other = Entertainer::factory()->create(['name' => 'Magische Milan', 'active' => true]);
        $match->skills()->attach($schminker);
        $other->skills()->attach($goochelaar);

        $this->get(route('entertainers.index', ['skill' => 'schminker']))
            ->assertOk()
            ->assertSee('Sanne Schminkster')
            ->assertDontSee('Magische Milan');
    }

    public function test_livewire_entertainer_filter_works(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $goochelaar = Skill::factory()->create(['name' => 'Goochelaar', 'slug' => 'goochelaar']);
        $match = Entertainer::factory()->create(['name' => 'Sanne Schminkster', 'active' => true]);
        $other = Entertainer::factory()->create(['name' => 'Magische Milan', 'active' => true]);
        $match->skills()->attach($schminker);
        $other->skills()->attach($goochelaar);

        Livewire::test('entertainer-index')
            ->set('skill', 'schminker')
            ->assertSee('Sanne Schminkster')
            ->assertDontSee('Magische Milan');
    }

    public function test_published_landing_page_renders_with_seo_metadata(): void
    {
        $landingPage = LandingPage::query()->create([
            'title' => 'Schminker voor kinderfeestje',
            'slug' => 'schminker-kinderfeestje',
            'intro' => 'Boek snel een professionele schminker.',
            'body' => 'Schminken maakt elk kinderfeest net wat feestelijker.',
            'cta_label' => 'Bekijk entertainers',
            'cta_url' => route('entertainers.index'),
            'seo_title' => 'Schminker kinderfeestje boeken',
            'meta_description' => 'Vind en boek een schminker voor een kinderfeestje.',
            'is_published' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('landing-pages.show', $landingPage))
            ->assertOk()
            ->assertSee('<title>Schminker kinderfeestje boeken</title>', false)
            ->assertSee('name="description" content="Vind en boek een schminker voor een kinderfeestje."', false)
            ->assertSee('Schminker voor kinderfeestje')
            ->assertSee('Schminken maakt elk kinderfeest net wat feestelijker.');
    }

    public function test_unpublished_landing_page_is_not_public(): void
    {
        $landingPage = LandingPage::query()->create([
            'title' => 'Concept pagina',
            'slug' => 'concept-pagina',
            'is_published' => false,
        ]);

        $this->get(route('landing-pages.show', $landingPage))
            ->assertNotFound();
    }

    public function test_landing_page_does_not_render_unsafe_cta_url(): void
    {
        $landingPage = LandingPage::query()->create([
            'title' => 'Veilige CTA',
            'slug' => 'veilige-cta',
            'cta_label' => 'Klik hier',
            'cta_url' => 'javascript:alert(1)',
            'is_published' => true,
        ]);

        $this->get(route('landing-pages.show', $landingPage))
            ->assertOk()
            ->assertDontSee('javascript:alert(1)', false)
            ->assertDontSee('Klik hier');
    }

    public function test_sitemap_includes_indexable_landing_pages(): void
    {
        LandingPage::query()->create([
            'title' => 'Ballonnenclown boeken',
            'slug' => 'ballonnenclown-boeken',
            'is_published' => true,
        ]);

        LandingPage::query()->create([
            'title' => 'Noindex pagina',
            'slug' => 'noindex-pagina',
            'is_published' => true,
            'noindex' => true,
        ]);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml')
            ->assertSee(url('/ballonnenclown-boeken'))
            ->assertDontSee(url('/noindex-pagina'));
    }

    public function test_livewire_public_smart_filters_work(): void
    {
        $match = Entertainer::factory()->create([
            'name' => 'Sanne Schminkster',
            'active' => true,
            'audience_age_range' => '3 t/m 8 jaar',
            'event_types' => ['Kinderfeestje', 'School'],
            'languages' => ['Nederlands', 'Engels'],
            'working_radius_km' => 40,
            'rating' => 4.8,
            'reviews_count' => 18,
        ]);
        $other = Entertainer::factory()->create([
            'name' => 'Magische Milan',
            'active' => true,
            'audience_age_range' => '9 t/m 12 jaar',
            'event_types' => ['Bedrijfsevent'],
            'languages' => ['Duits'],
            'working_radius_km' => 90,
            'rating' => 3.9,
            'reviews_count' => 4,
        ]);
        Rate::factory()->create([
            'entertainer_id' => $match->id,
            'customer_type' => CustomerType::Consumer,
            'starting_rate_cents' => 15000,
        ]);
        Rate::factory()->create([
            'entertainer_id' => $other->id,
            'customer_type' => CustomerType::Consumer,
            'starting_rate_cents' => 27500,
        ]);

        Livewire::test('entertainer-index')
            ->set('age', '7')
            ->set('eventType', 'Kinderfeestje')
            ->set('language', 'Engels')
            ->set('maxRadius', '50')
            ->set('maxPrice', '200')
            ->set('minRating', '4.5')
            ->assertSee('Sanne Schminkster')
            ->assertDontSee('Magische Milan');
    }

    public function test_livewire_direct_availability_filter_works(): void
    {
        $date = now()->addWeek()->toDateString();
        $match = Entertainer::factory()->create(['name' => 'Beschikbare Britt', 'active' => true]);
        $other = Entertainer::factory()->create(['name' => 'Bezette Bo', 'active' => true]);
        Availability::factory()->create([
            'entertainer_id' => $match->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'status' => AvailabilityStatus::Available,
        ]);
        Availability::factory()->create([
            'entertainer_id' => $other->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'status' => AvailabilityStatus::Unavailable,
        ]);

        Livewire::test('entertainer-index')
            ->set('date', $date)
            ->set('startTime', '11:00')
            ->set('endTime', '13:00')
            ->set('availableOnly', true)
            ->assertSee('Beschikbare Britt')
            ->assertDontSee('Bezette Bo');
    }

    public function test_entertainer_detail_page_works(): void
    {
        $entertainer = Entertainer::factory()->create(['name' => 'DJ Daan Kids', 'slug' => 'dj-daan-kids', 'active' => true]);

        $this->get(route('entertainers.show', $entertainer))
            ->assertOk()
            ->assertSee('DJ Daan Kids')
            ->assertSee('Beschikbaarheid controleren');
    }

    public function test_availability_check_works(): void
    {
        $entertainer = Entertainer::factory()->create(['active' => true]);
        Availability::factory()->create([
            'entertainer_id' => $entertainer->id,
            'date' => now()->addWeek()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '16:00',
            'status' => AvailabilityStatus::Available,
        ]);

        $this->assertTrue(app(CheckEntertainerAvailability::class)->handle(
            $entertainer,
            now()->addWeek()->toDateString(),
            '11:00',
            '13:00',
        ));
    }

    public function test_availability_check_rejects_overlapping_availability_conflicts(): void
    {
        $entertainer = Entertainer::factory()->create(['active' => true]);
        $date = now()->addWeek()->toDateString();

        Availability::factory()->create([
            'entertainer_id' => $entertainer->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'status' => AvailabilityStatus::Available,
        ]);
        Availability::factory()->create([
            'entertainer_id' => $entertainer->id,
            'date' => $date,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'status' => AvailabilityStatus::Option,
        ]);

        $this->assertFalse(app(CheckEntertainerAvailability::class)->handle($entertainer, $date, '11:30', '12:30'));
    }

    public function test_availability_check_uses_weekly_availability_rules(): void
    {
        $entertainer = Entertainer::factory()->create(['active' => true]);
        $date = now()->next('Saturday')->toDateString();

        AvailabilityRule::query()->create([
            'entertainer_id' => $entertainer->id,
            'name' => 'Elke zaterdag',
            'rule_type' => 'weekly',
            'weekdays' => [6],
            'starts_on' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '16:00',
            'status' => AvailabilityStatus::Available,
        ]);

        $this->assertTrue(app(CheckEntertainerAvailability::class)->handle($entertainer, $date, '11:00', '12:00'));
    }

    public function test_specific_unavailable_block_overrules_weekly_availability_rule(): void
    {
        $entertainer = Entertainer::factory()->create(['active' => true]);
        $date = now()->next('Saturday')->toDateString();

        AvailabilityRule::query()->create([
            'entertainer_id' => $entertainer->id,
            'name' => 'Elke zaterdag',
            'rule_type' => 'weekly',
            'weekdays' => [6],
            'starts_on' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '16:00',
            'status' => AvailabilityStatus::Available,
        ]);
        Availability::factory()->create([
            'entertainer_id' => $entertainer->id,
            'date' => $date,
            'start_time' => '11:30',
            'end_time' => '12:30',
            'status' => AvailabilityStatus::Unavailable,
        ]);

        $this->assertFalse(app(CheckEntertainerAvailability::class)->handle($entertainer, $date, '11:00', '12:00'));
    }

    public function test_availability_check_rejects_confirmed_booking_conflicts(): void
    {
        $entertainer = Entertainer::factory()->create(['active' => true]);
        $date = now()->addWeek()->toDateString();

        Availability::factory()->create([
            'entertainer_id' => $entertainer->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'status' => AvailabilityStatus::Available,
        ]);
        BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'event_date' => $date,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'status' => BookingStatus::Confirmed,
        ]);

        $this->assertFalse(app(CheckEntertainerAvailability::class)->handle($entertainer, $date, '11:30', '12:30'));
    }

    public function test_booking_request_can_be_created(): void
    {
        $entertainer = Entertainer::factory()->create(['active' => true, 'slug' => 'sanne']);
        $skill = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $entertainer->skills()->attach($skill);

        $this->post(route('booking-requests.store', $entertainer), $this->bookingRequestPayload([
            'request_type' => 'specific',
            'skill_id' => $skill->id,
        ]))->assertRedirect(route('booking-requests.thanks'));

        $this->assertDatabaseHas('booking_requests', [
            'entertainer_id' => $entertainer->id,
            'email' => 'marieke@example.com',
            'status' => BookingStatus::New->value,
        ]);
    }

    public function test_booking_request_stores_automatic_price_indication(): void
    {
        $skill = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $entertainer = Entertainer::factory()->create([
            'active' => true,
            'slug' => 'sanne',
            'region' => 'Utrecht',
        ]);
        $entertainer->skills()->attach($skill);
        Rate::factory()->create([
            'entertainer_id' => $entertainer->id,
            'customer_type' => CustomerType::Consumer,
            'starting_rate_cents' => 10000,
            'hourly_rate_cents' => 5000,
            'minimum_hours' => 1,
        ]);

        $this->post(route('booking-requests.store', $entertainer), $this->bookingRequestPayload([
            'request_type' => 'specific',
            'skill_id' => $skill->id,
            'event_date' => now()->addWeeks(2)->next('Monday')->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'event_region' => 'Utrecht',
            'travel_time_minutes' => 30,
            'children_count' => 12,
        ]))->assertRedirect(route('booking-requests.thanks'));

        $this->assertDatabaseHas('booking_requests', [
            'entertainer_id' => $entertainer->id,
            'price_indication_min_cents' => 20000,
            'price_indication_max_cents' => 20000,
            'price_indication_currency' => 'EUR',
        ]);
    }

    public function test_customer_can_request_specific_schminker(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $entertainer = Entertainer::factory()->create(['active' => true, 'slug' => 'schminker-sophie']);
        $entertainer->skills()->attach($schminker);

        $this->post(route('booking-requests.store', $entertainer), $this->bookingRequestPayload([
            'request_type' => 'specific',
            'skill_id' => $schminker->id,
        ]))->assertRedirect(route('booking-requests.thanks'));

        $this->assertDatabaseHas('booking_requests', [
            'entertainer_id' => $entertainer->id,
            'skill_id' => $schminker->id,
            'email' => 'marieke@example.com',
        ]);
    }

    public function test_customer_can_create_general_request_for_schminker_skill(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $available = $this->availableEntertainerForSkill($schminker);

        $this->post(route('booking-requests.general.store'), $this->bookingRequestPayload([
            'request_type' => 'general',
            'skill_id' => $schminker->id,
        ]))->assertRedirect(route('booking-requests.thanks'));

        $this->assertDatabaseHas('booking_requests', [
            'entertainer_id' => null,
            'skill_id' => $schminker->id,
            'status' => BookingStatus::New->value,
        ]);

        $this->assertDatabaseHas('booking_request_matches', [
            'entertainer_id' => $available->id,
            'status' => 'beschikbaar',
        ]);
    }

    public function test_customer_can_use_customer_portal_for_own_booking_request(): void
    {
        $this->createRoles();
        $customer = User::factory()->create(['email' => 'marieke@example.com']);
        $customer->assignRole('klant');
        $bookingRequest = BookingRequest::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'marieke@example.com',
            'status' => BookingStatus::Option,
            'customer_message' => 'De offerte staat klaar in je portaal.',
            'quote_sent_at' => now(),
            'quote_valid_until' => now()->addWeek(),
            'quote_total_cents' => 25000,
        ]);

        $this->actingAs($customer)
            ->get(route('customer-portal.index'))
            ->assertOk()
            ->assertSee('Klantportaal')
            ->assertSee($bookingRequest->city);

        $this->actingAs($customer)
            ->get(route('customer-portal.show', $bookingRequest))
            ->assertOk()
            ->assertSee('Gegevens wijzigen')
            ->assertSee('De offerte staat klaar in je portaal.');

        $this->actingAs($customer)
            ->patch(route('customer-portal.update', $bookingRequest), [
                'customer_type' => CustomerType::Consumer->value,
                'name' => 'Marieke de Vries',
                'company_name' => null,
                'email' => 'marieke@example.com',
                'phone' => '0612345678',
                'event_date' => now()->addWeeks(3)->toDateString(),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'address' => 'Nieuweweg 2',
                'postal_code' => '1234 AB',
                'city' => 'Utrecht',
                'children_count' => 14,
                'children_ages' => '5-7 jaar',
                'message' => 'Graag buiten starten.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('booking_requests', [
            'id' => $bookingRequest->id,
            'name' => 'Marieke de Vries',
            'city' => 'Utrecht',
        ]);

        $this->actingAs($customer)
            ->post(route('customer-portal.accept-quote', $bookingRequest))
            ->assertRedirect();

        $this->assertDatabaseHas('booking_requests', [
            'id' => $bookingRequest->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
        $this->assertNotNull($bookingRequest->refresh()->quote_accepted_at);

        $download = $this->actingAs($customer)
            ->get(route('customer-portal.download', $bookingRequest));

        $download->assertDownload('aanvraag-'.$bookingRequest->id.'-confirmation.pdf');
        $this->assertStringStartsWith('%PDF-', $download->streamedContent());
    }

    public function test_customer_cannot_accept_quote_before_quote_is_sent(): void
    {
        $this->createRoles();
        $customer = User::factory()->create(['email' => 'marieke@example.com']);
        $customer->assignRole('klant');
        $bookingRequest = BookingRequest::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'marieke@example.com',
            'status' => BookingStatus::Option,
            'quote_sent_at' => null,
            'quote_total_cents' => null,
            'quote_accepted_at' => null,
        ]);

        $this->actingAs($customer)
            ->post(route('customer-portal.accept-quote', $bookingRequest))
            ->assertStatus(422);

        $bookingRequest->refresh();

        $this->assertSame(BookingStatus::Option, $bookingRequest->status);
        $this->assertNull($bookingRequest->quote_accepted_at);
    }

    public function test_customer_cannot_update_booking_details_after_quote_acceptance(): void
    {
        $this->createRoles();
        $customer = User::factory()->create(['email' => 'marieke@example.com']);
        $customer->assignRole('klant');
        $bookingRequest = BookingRequest::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'marieke@example.com',
            'status' => BookingStatus::Confirmed,
            'quote_accepted_at' => now(),
            'city' => 'Amsterdam',
        ]);

        $this->actingAs($customer)
            ->patch(route('customer-portal.update', $bookingRequest), [
                'customer_type' => CustomerType::Consumer->value,
                'name' => 'Marieke de Vries',
                'company_name' => null,
                'email' => 'marieke@example.com',
                'phone' => '0612345678',
                'event_date' => now()->addWeeks(3)->toDateString(),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'address' => 'Nieuweweg 2',
                'postal_code' => '1234 AB',
                'city' => 'Utrecht',
                'children_count' => 14,
                'children_ages' => '5-7 jaar',
                'message' => 'Graag buiten starten.',
            ])
            ->assertConflict();

        $this->assertSame('Amsterdam', $bookingRequest->refresh()->city);
    }

    public function test_customer_cannot_view_another_customers_booking_request(): void
    {
        $this->createRoles();
        $customer = User::factory()->create(['email' => 'marieke@example.com']);
        $customer->assignRole('klant');
        $bookingRequest = BookingRequest::factory()->create([
            'email' => 'iemand-anders@example.com',
        ]);

        $this->actingAs($customer)
            ->get(route('customer-portal.show', $bookingRequest))
            ->assertForbidden();
    }

    public function test_entertainer_can_respond_to_general_request_match(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create([
            'user_id' => $user->id,
        ]);
        $bookingRequest = BookingRequest::factory()->create(['entertainer_id' => null]);
        $match = BookingRequestMatch::query()->create([
            'booking_request_id' => $bookingRequest->id,
            'entertainer_id' => $entertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('dashboard.matches.response', $match), [
                'response' => 'accepted',
                'price_indication_euros' => '275.50',
                'response_message' => 'Ik ben beschikbaar en neem alle materialen mee.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('booking_request_matches', [
            'id' => $match->id,
            'status' => BookingRequestMatchStatus::Accepted->value,
            'price_indication_cents' => 27550,
            'response_message' => 'Ik ben beschikbaar en neem alle materialen mee.',
        ]);
        $this->assertDatabaseHas('booking_requests', [
            'id' => $bookingRequest->id,
            'status' => BookingStatus::Option->value,
        ]);
        $this->assertNotNull($match->refresh()->responded_at);
    }

    public function test_entertainer_dashboard_only_shows_own_general_request_matches(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create([
            'user_id' => $user->id,
        ]);
        $otherEntertainer = Entertainer::factory()->create();
        $ownRequest = BookingRequest::factory()->create([
            'entertainer_id' => null,
            'name' => 'Eigen algemene aanvraag',
        ]);
        $otherRequest = BookingRequest::factory()->create([
            'entertainer_id' => null,
            'name' => 'Andere algemene aanvraag',
        ]);

        BookingRequestMatch::query()->create([
            'booking_request_id' => $ownRequest->id,
            'entertainer_id' => $entertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
        ]);
        BookingRequestMatch::query()->create([
            'booking_request_id' => $otherRequest->id,
            'entertainer_id' => $otherEntertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Eigen algemene aanvraag')
            ->assertDontSee('Andere algemene aanvraag');
    }

    public function test_entertainer_can_reject_own_general_request_match(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create([
            'user_id' => $user->id,
        ]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => null,
            'status' => BookingStatus::New,
        ]);
        $match = BookingRequestMatch::query()->create([
            'booking_request_id' => $bookingRequest->id,
            'entertainer_id' => $entertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('dashboard.matches.response', $match), [
                'response' => 'rejected',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('booking_request_matches', [
            'id' => $match->id,
            'status' => BookingRequestMatchStatus::Rejected->value,
        ]);
        $this->assertDatabaseHas('booking_requests', [
            'id' => $bookingRequest->id,
            'status' => BookingStatus::New->value,
        ]);
    }

    public function test_entertainer_cannot_respond_to_other_entertainers_general_request_match(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        Entertainer::factory()->create(['user_id' => $user->id]);
        $otherEntertainer = Entertainer::factory()->create();
        $match = BookingRequestMatch::query()->create([
            'booking_request_id' => BookingRequest::factory()->create(['entertainer_id' => null])->id,
            'entertainer_id' => $otherEntertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('dashboard.matches.response', $match), [
                'response' => 'accepted',
            ])
            ->assertForbidden();
    }

    public function test_customer_can_choose_responded_general_request_match(): void
    {
        Mail::fake();

        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => null,
            'customer_selection_token' => 'customer-token',
            'status' => BookingStatus::New,
            'email' => 'klant@example.com',
        ]);
        $chosenUser = User::factory()->create(['email' => 'gekozen@example.com']);
        $otherUser = User::factory()->create(['email' => 'ander@example.com']);
        $chosenEntertainer = Entertainer::factory()->create(['user_id' => $chosenUser->id]);
        $otherEntertainer = Entertainer::factory()->create(['user_id' => $otherUser->id]);
        $chosenMatch = BookingRequestMatch::query()->create([
            'booking_request_id' => $bookingRequest->id,
            'entertainer_id' => $chosenEntertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'price_indication_cents' => 25000,
            'response_message' => 'Beschikbaar.',
            'matched_at' => now(),
            'responded_at' => now(),
        ]);
        $otherMatch = BookingRequestMatch::query()->create([
            'booking_request_id' => $bookingRequest->id,
            'entertainer_id' => $otherEntertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
            'responded_at' => now(),
        ]);

        $this->post(route('booking-requests.matches.select', [$bookingRequest, $chosenMatch]), [
            'token' => 'customer-token',
        ])->assertRedirect(route('booking-requests.matches.index', [$bookingRequest, 'customer-token']));

        $this->assertDatabaseHas('booking_requests', [
            'id' => $bookingRequest->id,
            'entertainer_id' => $chosenEntertainer->id,
            'status' => BookingStatus::Option->value,
        ]);
        $this->assertDatabaseHas('booking_request_matches', [
            'id' => $chosenMatch->id,
            'status' => BookingRequestMatchStatus::Accepted->value,
        ]);
        $this->assertDatabaseHas('booking_request_matches', [
            'id' => $otherMatch->id,
            'status' => BookingRequestMatchStatus::Expired->value,
        ]);
        Mail::assertSentCount(3);
    }

    public function test_customer_cannot_choose_expired_general_request_match_after_selection(): void
    {
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => null,
            'customer_selection_token' => 'customer-token',
            'status' => BookingStatus::New,
        ]);
        $chosenEntertainer = Entertainer::factory()->create();
        $expiredEntertainer = Entertainer::factory()->create();
        $chosenMatch = BookingRequestMatch::query()->create([
            'booking_request_id' => $bookingRequest->id,
            'entertainer_id' => $chosenEntertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
            'responded_at' => now(),
        ]);
        $expiredMatch = BookingRequestMatch::query()->create([
            'booking_request_id' => $bookingRequest->id,
            'entertainer_id' => $expiredEntertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
            'responded_at' => now(),
        ]);

        $this->post(route('booking-requests.matches.select', [$bookingRequest, $chosenMatch]), [
            'token' => 'customer-token',
        ])->assertRedirect(route('booking-requests.matches.index', [$bookingRequest, 'customer-token']));

        $this->post(route('booking-requests.matches.select', [$bookingRequest, $expiredMatch]), [
            'token' => 'customer-token',
        ])->assertSessionHasErrors('match');

        $this->assertDatabaseHas('booking_requests', [
            'id' => $bookingRequest->id,
            'entertainer_id' => $chosenEntertainer->id,
            'status' => BookingStatus::Option->value,
        ]);
        $this->assertDatabaseHas('booking_request_matches', [
            'id' => $expiredMatch->id,
            'status' => BookingRequestMatchStatus::Expired->value,
            'selected_at' => null,
        ]);
    }

    public function test_customer_selection_token_expires(): void
    {
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => null,
            'customer_selection_token' => 'expired-customer-token',
            'customer_selection_expires_at' => now()->subMinute(),
            'status' => BookingStatus::New,
        ]);
        $match = BookingRequestMatch::query()->create([
            'booking_request_id' => $bookingRequest->id,
            'entertainer_id' => Entertainer::factory()->create()->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
            'responded_at' => now(),
        ]);

        $this->post(route('booking-requests.matches.select', [$bookingRequest, $match]), [
            'token' => 'expired-customer-token',
        ])->assertGone();
    }

    public function test_available_entertainer_finder_only_returns_active_available_schminkers(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $available = $this->availableEntertainerForSkill($schminker);
        $inactive = $this->availableEntertainerForSkill($schminker, ['active' => false]);

        $matches = app(FindAvailableEntertainersForRequest::class)->handle(
            $schminker,
            now()->addWeeks(2)->toDateString(),
            '13:00',
            '15:00',
        );

        $this->assertTrue($matches->contains($available));
        $this->assertFalse($matches->contains($inactive));
    }

    public function test_available_entertainer_finder_excludes_entertainers_without_schminker_skill(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $goochelaar = Skill::factory()->create(['name' => 'Goochelaar', 'slug' => 'goochelaar']);
        $available = $this->availableEntertainerForSkill($schminker);
        $other = $this->availableEntertainerForSkill($goochelaar);

        $matches = app(FindAvailableEntertainersForRequest::class)->handle($schminker, now()->addWeeks(2)->toDateString(), '13:00', '15:00');

        $this->assertTrue($matches->contains($available));
        $this->assertFalse($matches->contains($other));
    }

    public function test_available_entertainer_finder_excludes_conflicting_entertainers(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $available = $this->availableEntertainerForSkill($schminker);
        $conflicting = $this->availableEntertainerForSkill($schminker);

        BookingRequest::factory()->create([
            'entertainer_id' => $conflicting->id,
            'skill_id' => $schminker->id,
            'event_date' => now()->addWeeks(2)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => BookingStatus::Option,
        ]);

        $matches = app(FindAvailableEntertainersForRequest::class)->handle($schminker, now()->addWeeks(2)->toDateString(), '13:00', '15:00');

        $this->assertTrue($matches->contains($available));
        $this->assertFalse($matches->contains($conflicting));
    }

    public function test_available_entertainer_finder_excludes_booked_entertainers(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $available = $this->availableEntertainerForSkill($schminker);
        $booked = $this->availableEntertainerForSkill($schminker);

        Availability::factory()->create([
            'entertainer_id' => $booked->id,
            'date' => now()->addWeeks(2)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => AvailabilityStatus::Booked,
        ]);

        $matches = app(FindAvailableEntertainersForRequest::class)->handle($schminker, now()->addWeeks(2)->toDateString(), '13:00', '15:00');

        $this->assertTrue($matches->contains($available));
        $this->assertFalse($matches->contains($booked));
    }

    public function test_available_entertainer_finder_excludes_general_request_option_conflicts(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $available = $this->availableEntertainerForSkill($schminker);
        $conflicting = $this->availableEntertainerForSkill($schminker);
        $existingGeneralRequest = BookingRequest::factory()->create([
            'entertainer_id' => null,
            'skill_id' => $schminker->id,
            'event_date' => now()->addWeeks(2)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => BookingStatus::Option,
        ]);

        BookingRequestMatch::query()->create([
            'booking_request_id' => $existingGeneralRequest->id,
            'entertainer_id' => $conflicting->id,
            'status' => BookingRequestMatchStatus::Accepted,
            'matched_at' => now(),
            'responded_at' => now(),
        ]);

        $matches = app(FindAvailableEntertainersForRequest::class)->handle($schminker, now()->addWeeks(2)->toDateString(), '13:00', '15:00');

        $this->assertTrue($matches->contains($available));
        $this->assertFalse($matches->contains($conflicting));
    }

    public function test_available_entertainer_finder_ignores_expired_general_request_matches(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $available = $this->availableEntertainerForSkill($schminker);
        $expired = $this->availableEntertainerForSkill($schminker);
        $existingGeneralRequest = BookingRequest::factory()->create([
            'entertainer_id' => null,
            'skill_id' => $schminker->id,
            'event_date' => now()->addWeeks(2)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => BookingStatus::Option,
        ]);

        BookingRequestMatch::query()->create([
            'booking_request_id' => $existingGeneralRequest->id,
            'entertainer_id' => $expired->id,
            'status' => BookingRequestMatchStatus::Expired,
            'matched_at' => now(),
            'responded_at' => now(),
        ]);

        $matches = app(FindAvailableEntertainersForRequest::class)->handle($schminker, now()->addWeeks(2)->toDateString(), '13:00', '15:00');

        $this->assertTrue($matches->contains($available));
        $this->assertTrue($matches->contains($expired));
    }

    public function test_booking_request_without_entertainer_and_without_skill_fails(): void
    {
        $this->post(route('booking-requests.general.store'), $this->bookingRequestPayload([
            'request_type' => 'general',
            'skill_id' => null,
        ]))->assertSessionHasErrors('skill_id');
    }

    public function test_booking_request_with_skill_and_specific_entertainer_uses_chosen_request_type(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $entertainer = Entertainer::factory()->create(['active' => true, 'slug' => 'sophie']);
        $entertainer->skills()->attach($schminker);

        $this->post(route('booking-requests.store', $entertainer), $this->bookingRequestPayload([
            'request_type' => 'specific',
            'skill_id' => $schminker->id,
        ]))->assertRedirect(route('booking-requests.thanks'));

        $this->assertDatabaseHas('booking_requests', [
            'entertainer_id' => $entertainer->id,
            'skill_id' => $schminker->id,
        ]);

        $this->post(route('booking-requests.general.store'), $this->bookingRequestPayload([
            'request_type' => 'general',
            'skill_id' => $schminker->id,
            'entertainer_id' => $entertainer->id,
        ]))->assertSessionHasErrors('entertainer_id');
    }

    public function test_specific_booking_request_does_not_trust_entertainer_id_from_payload(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $entertainer = Entertainer::factory()->create(['active' => true, 'slug' => 'sophie']);
        $otherEntertainer = Entertainer::factory()->create(['active' => true, 'slug' => 'iemand-anders']);
        $entertainer->skills()->attach($schminker);
        $otherEntertainer->skills()->attach($schminker);

        $this->post(route('booking-requests.store', $entertainer), $this->bookingRequestPayload([
            'request_type' => 'specific',
            'skill_id' => $schminker->id,
            'entertainer_id' => $otherEntertainer->id,
        ]))->assertSessionHasErrors('entertainer_id');

        $this->assertDatabaseMissing('booking_requests', [
            'entertainer_id' => $otherEntertainer->id,
            'email' => 'marieke@example.com',
        ]);
    }

    public function test_booking_request_validation_rejects_invalid_input(): void
    {
        $entertainer = Entertainer::factory()->create(['active' => true, 'slug' => 'sanne']);
        $entertainer->skills()->attach(Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']));

        $this->post(route('booking-requests.store', $entertainer), [
            'customer_type' => 'b2b',
            'name' => 'Marieke Jansen',
            'email' => 'marieke@example.com',
            'phone' => '0612345678',
            'event_date' => now()->subDay()->toDateString(),
            'start_time' => '15:00',
            'end_time' => '13:00',
            'address' => 'Dorpsstraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Utrecht',
            'desired_skills' => ['Goochelaar'],
        ])->assertSessionHasErrors([
            'company_name',
            'event_date',
            'end_time',
            'desired_skills',
        ]);
    }

    public function test_setup_can_create_first_admin_user(): void
    {
        $this->get(route('setup'))
            ->assertOk()
            ->assertSee('Eerste gebruiker aanmaken');

        $this->post(route('setup.store'), [
            'name' => 'Eerste Beheerder',
            'email' => 'admin@example.com',
            'password' => 'veilig-wachtwoord',
            'password_confirmation' => 'veilig-wachtwoord',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('veilig-wachtwoord', $user->password));
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_setup_redirects_when_user_already_exists(): void
    {
        User::factory()->create();

        $this->get(route('setup'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Setup is al uitgevoerd.');
    }

    public function test_setup_post_does_not_create_user_when_user_already_exists(): void
    {
        User::factory()->create();

        $this->post(route('setup.store'), [
            'name' => 'Tweede Beheerder',
            'email' => 'second@example.com',
            'password' => 'veilig-wachtwoord',
            'password_confirmation' => 'veilig-wachtwoord',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('users', [
            'email' => 'second@example.com',
        ]);
    }

    public function test_entertainer_can_manage_own_booking_request_status(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create(['user_id' => $user->id]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'status' => BookingStatus::New,
        ]);

        $this->actingAs($user)
            ->patch(route('dashboard.booking-requests.status', $bookingRequest), ['status' => 'bevestigd'])
            ->assertRedirect();

        $this->assertDatabaseHas('booking_requests', [
            'id' => $bookingRequest->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
        $this->assertDatabaseHas('booking_request_events', [
            'booking_request_id' => $bookingRequest->id,
            'type' => BookingRequestEventType::StatusChange->value,
            'new_status' => BookingStatus::Confirmed->value,
            'user_id' => $user->id,
        ]);
    }

    public function test_booking_request_creation_writes_customer_message_to_timeline(): void
    {
        $entertainer = Entertainer::factory()->create(['active' => true, 'slug' => 'tijdlijn-sanne']);
        $entertainer->skills()->attach(Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']));

        $this->post(route('booking-requests.store', $entertainer), $this->bookingRequestPayload([
            'message' => 'Kunnen jullie ook glittertattoos meenemen?',
        ]))->assertRedirect(route('booking-requests.thanks'));

        $bookingRequest = BookingRequest::query()->where('email', 'marieke@example.com')->latest()->firstOrFail();

        $this->assertDatabaseHas('booking_request_events', [
            'booking_request_id' => $bookingRequest->id,
            'type' => BookingRequestEventType::CustomerMessage->value,
            'actor_type' => 'customer',
            'actor_name' => 'Marieke Jansen',
            'body' => 'Kunnen jullie ook glittertattoos meenemen?',
            'visible_to_entertainer' => true,
        ]);
    }

    public function test_entertainer_can_add_booking_request_timeline_event(): void
    {
        $this->createRoles();
        $user = User::factory()->create(['name' => 'Sanne Schmink']);
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create(['user_id' => $user->id]);
        $bookingRequest = BookingRequest::factory()->create(['entertainer_id' => $entertainer->id]);

        $this->actingAs($user)
            ->post(route('dashboard.booking-requests.events.store', $bookingRequest), [
                'type' => BookingRequestEventType::EntertainerResponse->value,
                'body' => 'Ik bel de klant morgen terug.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('booking_request_events', [
            'booking_request_id' => $bookingRequest->id,
            'type' => BookingRequestEventType::EntertainerResponse->value,
            'actor_type' => 'entertainer',
            'actor_name' => 'Sanne Schmink',
            'body' => 'Ik bel de klant morgen terug.',
            'visible_to_entertainer' => true,
            'user_id' => $user->id,
        ]);
    }

    public function test_entertainer_can_create_quote_and_customer_can_accept_it(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create([
            'user_id' => $user->id,
            'payment_provider' => PaymentProvider::Mollie,
            'cash_payment_enabled' => true,
            'travel_free_km' => 0,
            'max_travel_distance_km' => 40,
        ]);
        Rate::factory()->create([
            'entertainer_id' => $entertainer->id,
            'customer_type' => CustomerType::Consumer,
            'starting_rate_cents' => 5000,
            'hourly_rate_cents' => 7500,
            'minimum_hours' => 2,
            'travel_cost_cents_per_km' => 50,
        ]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'customer_type' => CustomerType::Consumer,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'status' => BookingStatus::New,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.booking-requests.quote', $bookingRequest), [
                'travel_distance_km' => 20,
                'valid_days' => 14,
            ])
            ->assertRedirect();

        $bookingRequest->refresh();

        $this->assertSame(21000, $bookingRequest->quote_total_cents);
        $this->assertSame(BookingStatus::Option, $bookingRequest->status);
        $this->assertNotNull($bookingRequest->quote_acceptance_token);

        $this->get(route('booking-quotes.show', $bookingRequest->quote_acceptance_token))
            ->assertOk()
            ->assertSee('€ 210,00')
            ->assertSee('Akkoord en boeking bevestigen');

        $this->post(route('booking-quotes.accept', $bookingRequest->quote_acceptance_token))
            ->assertRedirect(route('booking-quotes.show', $bookingRequest->quote_acceptance_token));

        $bookingRequest->refresh();

        $this->assertSame(BookingStatus::Confirmed, $bookingRequest->status);
        $this->assertNotNull($bookingRequest->quote_accepted_at);
        $this->assertSame('ready_for_entertainer', $bookingRequest->invoice_status);
        $this->assertSame(AccountingProvider::Manual->value, $bookingRequest->invoice_provider);
        $this->assertSame(PaymentProvider::Mollie->value, $bookingRequest->payment_provider);
        $this->assertTrue($bookingRequest->cash_payment_allowed);
    }

    public function test_public_quote_link_cannot_confirm_cancelled_booking(): void
    {
        $bookingRequest = BookingRequest::factory()->create([
            'status' => BookingStatus::Cancelled,
            'quote_sent_at' => now(),
            'quote_total_cents' => 10000,
            'quote_acceptance_token' => 'cancelled-quote-token',
            'quote_valid_until' => now()->addDay(),
        ]);

        $this->post(route('booking-quotes.accept', $bookingRequest->quote_acceptance_token))
            ->assertUnprocessable();

        $this->assertSame(BookingStatus::Cancelled, $bookingRequest->refresh()->status);
        $this->assertNull($bookingRequest->quote_accepted_at);
    }

    public function test_public_quote_link_cannot_accept_incomplete_quote(): void
    {
        $bookingRequest = BookingRequest::factory()->create([
            'status' => BookingStatus::Option,
            'quote_sent_at' => now(),
            'quote_total_cents' => null,
            'quote_acceptance_token' => 'incomplete-quote-token',
            'quote_valid_until' => now()->addDay(),
        ]);

        $this->post(route('booking-quotes.accept', $bookingRequest->quote_acceptance_token))
            ->assertUnprocessable();

        $this->assertSame(BookingStatus::Option, $bookingRequest->refresh()->status);
        $this->assertNull($bookingRequest->quote_accepted_at);
    }

    public function test_quote_uses_free_travel_kilometers_and_maximum_distance(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create([
            'user_id' => $user->id,
            'travel_free_km' => 10,
            'max_travel_distance_km' => 25,
        ]);
        Rate::factory()->create([
            'entertainer_id' => $entertainer->id,
            'customer_type' => CustomerType::Consumer,
            'starting_rate_cents' => 5000,
            'hourly_rate_cents' => 7500,
            'minimum_hours' => 2,
            'travel_cost_cents_per_km' => 100,
        ]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'customer_type' => CustomerType::Consumer,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'status' => BookingStatus::New,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.booking-requests.quote', $bookingRequest), [
                'travel_distance_km' => 20,
                'valid_days' => 14,
            ])
            ->assertRedirect();

        $this->assertSame(21000, $bookingRequest->refresh()->quote_total_cents);
        $this->assertSame(1000, $bookingRequest->quote_travel_cents);

        $this->actingAs($user)
            ->post(route('dashboard.booking-requests.quote', $bookingRequest), [
                'travel_distance_km' => 26,
                'valid_days' => 14,
            ])
            ->assertSessionHasErrors('travel_distance_km');
    }

    public function test_accepting_quote_creates_external_invoice_and_payment_checkout_when_integrations_are_enabled(): void
    {
        Http::fake([
            'moneybird.com/*' => Http::response([
                'id' => 'mb-invoice-1',
                'invoice_id' => '2026-001',
                'url' => 'https://moneybird.test/invoices/mb-invoice-1',
            ]),
            'api.mollie.com/*' => Http::response([
                'id' => 'tr_mollie_1',
                '_links' => ['checkout' => ['href' => 'https://checkout.mollie.test/tr_mollie_1']],
            ]),
        ]);

        $entertainer = Entertainer::factory()->create([
            'accounting_provider' => AccountingProvider::Moneybird,
            'payment_provider' => PaymentProvider::Mollie,
            'deposit_percentage' => 50,
        ]);
        Rate::factory()->create([
            'entertainer_id' => $entertainer->id,
            'customer_type' => CustomerType::Consumer,
            'starting_rate_cents' => 10000,
            'hourly_rate_cents' => 5000,
            'minimum_hours' => 2,
        ]);
        EntertainerIntegration::query()->create([
            'entertainer_id' => $entertainer->id,
            'provider' => IntegrationProvider::Moneybird,
            'enabled' => true,
            'credentials' => ['api_token' => 'moneybird-token'],
            'settings' => ['administration_id' => '123456'],
        ]);
        EntertainerIntegration::query()->create([
            'entertainer_id' => $entertainer->id,
            'provider' => IntegrationProvider::Mollie,
            'enabled' => true,
            'credentials' => ['api_key' => 'mollie-token'],
        ]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'customer_type' => CustomerType::Consumer,
            'start_time' => '13:00',
            'end_time' => '15:00',
        ]);

        app(CreateBookingQuote::class)->handle($bookingRequest, 0, 14);

        $this->post(route('booking-quotes.accept', $bookingRequest->refresh()->quote_acceptance_token))
            ->assertRedirect();

        $bookingRequest->refresh();

        $this->assertSame('sent_to_moneybird', $bookingRequest->invoice_status);
        $this->assertSame('2026-001', $bookingRequest->invoice_reference);
        $this->assertSame('mb-invoice-1', $bookingRequest->invoice_external_id);
        $this->assertSame('tr_mollie_1', $bookingRequest->payment_external_id);
        $this->assertSame('https://checkout.mollie.test/tr_mollie_1', $bookingRequest->payment_checkout_url);

        $this->post(route('webhooks.payments', PaymentProvider::Mollie->value), [
            'id' => 'tr_mollie_1',
            'status' => 'paid',
        ])->assertNoContent();

        $this->assertSame('paid', $bookingRequest->refresh()->payment_status);
        $this->assertSame($bookingRequest->deposit_cents, $bookingRequest->paid_cents);
    }

    public function test_calendar_sync_creates_google_calendar_event_for_confirmed_booking(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'google-access-token']),
            'www.googleapis.com/calendar/*' => Http::response(['id' => 'calendar-event-1']),
        ]);

        $entertainer = Entertainer::factory()->create();
        EntertainerIntegration::query()->create([
            'entertainer_id' => $entertainer->id,
            'provider' => IntegrationProvider::GoogleCalendar,
            'enabled' => true,
            'credentials' => [
                'client_secret' => 'google-secret',
                'refresh_token' => 'google-refresh-token',
            ],
            'settings' => [
                'client_id' => 'google-client-id',
                'calendar_id' => 'entertainer@example.com',
            ],
        ]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'status' => BookingStatus::Confirmed,
            'calendar_synced_at' => null,
        ]);

        $this->artisan('calendar:sync-bookings')->assertExitCode(0);

        $bookingRequest->refresh();

        $this->assertSame('calendar-event-1', $bookingRequest->calendar_external_id);
        $this->assertSame('synced_google_calendar', $bookingRequest->calendar_sync_status);
        $this->assertNotNull($bookingRequest->calendar_synced_at);
    }

    public function test_entertainer_cannot_manage_other_entertainer_booking_request(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $otherEntertainer = Entertainer::factory()->create();
        $bookingRequest = BookingRequest::factory()->create(['entertainer_id' => $otherEntertainer->id]);

        $this->actingAs($user)
            ->patch(route('dashboard.booking-requests.status', $bookingRequest), ['status' => 'afgewezen'])
            ->assertForbidden();
    }

    public function test_entertainer_can_manage_own_rates(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('dashboard.rates.store'), [
                'customer_type' => CustomerType::Consumer->value,
                'starting_rate_cents' => 15000,
                'hourly_rate_cents' => 7500,
                'minimum_hours' => 2,
                'travel_cost_cents_per_km' => 45,
                'vat_included' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rates', [
            'entertainer_id' => $entertainer->id,
            'customer_type' => CustomerType::Consumer->value,
            'starting_rate_cents' => 15000,
        ]);
    }

    public function test_entertainer_can_manage_billing_and_integration_settings(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Facturatie')
            ->assertSee('Google Calendar')
            ->assertSee('Moneybird')
            ->assertSee('Mollie')
            ->assertSee('Outlook Calendar')
            ->assertSee('Postmark')
            ->assertSee('Pushover');

        $this->actingAs($user)
            ->patch(route('dashboard.billing.update'), [
                'accounting_provider' => AccountingProvider::Moneybird->value,
                'accounting_notes' => 'Facturen lopen via mijn eigen Moneybird-administratie.',
                'payment_provider' => PaymentProvider::Mollie->value,
                'cash_payment_enabled' => '1',
                'payment_notes' => 'Contant betalen mag op locatie.',
            ])
            ->assertRedirect();

        $moneybird = $entertainer->integrations()->where('provider', IntegrationProvider::Moneybird->value)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('dashboard.integrations.update', $moneybird), [
                'enabled' => '1',
                'api_token' => 'moneybird-token',
                'administration_id' => '123456',
                'workflow_id' => 'workflow-1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('entertainers', [
            'id' => $entertainer->id,
            'accounting_provider' => AccountingProvider::Moneybird->value,
            'payment_provider' => PaymentProvider::Mollie->value,
            'cash_payment_enabled' => true,
        ]);
        $this->assertTrue($moneybird->refresh()->enabled);
        $this->assertSame('moneybird-token', $moneybird->credentials['api_token']);
        $this->assertSame('123456', $moneybird->settings['administration_id']);

        $googleCalendar = $entertainer->integrations()->where('provider', IntegrationProvider::GoogleCalendar->value)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('dashboard.integrations.update', $googleCalendar), [
                'enabled' => '1',
                'client_id' => 'google-client-id',
                'client_secret' => 'google-client-secret',
                'refresh_token' => 'google-refresh-token',
                'calendar_id' => 'entertainer@example.com',
                'sync_direction' => 'two_way',
                'block_busy_events' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($googleCalendar->refresh()->enabled);
        $this->assertSame('google-client-secret', $googleCalendar->credentials['client_secret']);
        $this->assertSame('google-refresh-token', $googleCalendar->credentials['refresh_token']);
        $this->assertSame('google-client-id', $googleCalendar->settings['client_id']);
        $this->assertSame('entertainer@example.com', $googleCalendar->settings['calendar_id']);
        $this->assertSame('two_way', $googleCalendar->settings['sync_direction']);
        $this->assertTrue($googleCalendar->settings['block_busy_events']);
    }

    public function test_enabled_payment_integration_creates_checkout(): void
    {
        Http::fake([
            'api.mollie.com/v2/payments' => Http::response([
                'id' => 'tr_123',
                '_links' => ['checkout' => ['href' => 'https://payment.example/checkout']],
            ]),
        ]);

        $entertainer = Entertainer::factory()->create();
        $entertainer->integrations()->create([
            'provider' => IntegrationProvider::Mollie,
            'enabled' => true,
            'credentials' => ['api_key' => 'test_mollie_key'],
        ]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'deposit_cents' => 5000,
            'payment_provider' => PaymentProvider::Mollie->value,
            'quote_acceptance_token' => 'quote-token',
        ]);

        app(PaymentCheckoutService::class)->createCheckout($bookingRequest);

        $bookingRequest->refresh();

        $this->assertSame('tr_123', $bookingRequest->payment_external_id);
        $this->assertSame('https://payment.example/checkout', $bookingRequest->payment_checkout_url);
        $this->assertNotNull($bookingRequest->payment_checkout_created_at);
        Http::assertSentCount(1);
    }

    public function test_enabled_invoice_integration_creates_external_invoice(): void
    {
        Http::fake([
            'moneybird.com/api/v2/*/sales_invoices.json' => Http::response([
                'id' => 'invoice-123',
                'invoice_id' => 'MB-2026-001',
                'url' => 'https://moneybird.example/invoices/invoice-123',
            ]),
        ]);

        $entertainer = Entertainer::factory()->create();
        $entertainer->integrations()->create([
            'provider' => IntegrationProvider::Moneybird,
            'enabled' => true,
            'credentials' => ['api_token' => 'moneybird-token'],
            'settings' => ['administration_id' => '123456', 'workflow_id' => 'workflow-1'],
        ]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'invoice_provider' => AccountingProvider::Moneybird->value,
            'quote_total_cents' => 25000,
        ]);

        app(InvoiceIntegrationService::class)->createInvoiceInstruction($bookingRequest);

        $bookingRequest->refresh();

        $this->assertSame('sent_to_moneybird', $bookingRequest->invoice_status);
        $this->assertSame('MB-2026-001', $bookingRequest->invoice_reference);
        $this->assertSame('invoice-123', $bookingRequest->invoice_external_id);
        $this->assertSame('https://moneybird.example/invoices/invoice-123', $bookingRequest->invoice_url);
        Http::assertSentCount(1);
    }

    public function test_entertainer_can_manage_own_availability(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('dashboard.availabilities.store'), [
                'date' => now()->addWeek()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '16:00',
                'status' => AvailabilityStatus::Available->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('availabilities', [
            'entertainer_id' => $entertainer->id,
            'status' => AvailabilityStatus::Available->value,
        ]);
    }

    public function test_entertainer_can_manage_recurring_availability(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('dashboard.availability-rules.store'), [
                'name' => 'Elke zaterdag',
                'rule_type' => 'weekly',
                'weekdays' => [6],
                'starts_on' => now()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '16:00',
                'status' => AvailabilityStatus::Available->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('availability_rules', [
            'entertainer_id' => $entertainer->id,
            'name' => 'Elke zaterdag',
            'status' => AvailabilityStatus::Available->value,
        ]);
    }

    public function test_public_legal_documents_are_versioned_and_visible(): void
    {
        $document = LegalDocument::query()->where('type', LegalDocumentType::Privacy->value)->firstOrFail();
        $document->versions()->update(['published_at' => now()->subHour()]);
        $document->versions()->create([
            'version_label' => 'v2',
            'body' => "# Privacy v2\n\nNieuwe privacytekst.",
            'published_at' => now(),
        ]);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacyverklaring')
            ->assertSee('Versie v2')
            ->assertSee('Nieuwe privacytekst');
    }

    public function test_public_legal_documents_do_not_show_future_versions(): void
    {
        $document = LegalDocument::query()->where('type', LegalDocumentType::Privacy->value)->firstOrFail();
        $document->versions()->update(['published_at' => now()->subHour()]);
        $document->versions()->create([
            'version_label' => 'v2',
            'body' => "# Privacy v2\n\nNieuwe privacytekst.",
            'published_at' => now()->addMinute(),
        ]);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Versie v1')
            ->assertDontSee('Nieuwe privacytekst');
    }

    public function test_entertainer_can_configure_rich_profile_with_photos(): void
    {
        Storage::fake('public');
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch(route('dashboard.profile.update'), [
                'name' => 'Sanne Schminkster',
                'short_introduction' => 'Vrolijke schminkacts voor kinderfeestjes.',
                'bio' => 'Sanne maakt rustige kinderen snel op hun gemak.',
                'profile_highlights' => "Professionele schmink\nEigen materiaal\nOok glittertattoos",
                'audience_age_range' => '3 t/m 10 jaar',
                'event_types' => "Kinderfeestje\nSchool",
                'languages' => "Nederlands\nEngels",
                'performance_duration_minutes' => 90,
                'setup_time_minutes' => 30,
                'show_reel_url' => 'https://example.com/showreel',
                'practical_requirements' => 'Een tafel en goede verlichting.',
                'city' => 'Amsterdam',
                'region' => 'Noord-Holland',
                'working_radius_km' => 60,
                'travel_free_km' => 15,
                'max_travel_distance_km' => 80,
                'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
                'gallery_photos' => [
                    UploadedFile::fake()->image('gallery-1.jpg'),
                    UploadedFile::fake()->image('gallery-2.jpg'),
                ],
            ])
            ->assertRedirect();

        $entertainer->refresh();

        $this->assertSame(['Professionele schmink', 'Eigen materiaal', 'Ook glittertattoos'], $entertainer->profile_highlights);
        $this->assertSame('3 t/m 10 jaar', $entertainer->audience_age_range);
        $this->assertSame(['Kinderfeestje', 'School'], $entertainer->event_types);
        $this->assertSame(['Nederlands', 'Engels'], $entertainer->languages);
        $this->assertSame(90, $entertainer->performance_duration_minutes);
        $this->assertSame(15, $entertainer->travel_free_km);
        $this->assertSame(80, $entertainer->max_travel_distance_km);
        $this->assertCount(2, $entertainer->gallery_photo_paths);
        Storage::disk('public')->assertExists($entertainer->profile_photo_path);
        Storage::disk('public')->assertExists($entertainer->gallery_photo_paths[0]);
    }

    public function test_entertainer_gallery_is_limited_to_twelve_total_photos(): void
    {
        Storage::fake('public');
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        $entertainer = Entertainer::factory()->create([
            'user_id' => $user->id,
            'gallery_photo_paths' => collect(range(1, 11))
                ->map(fn (int $number): string => "entertainers/gallery/existing-{$number}.jpg")
                ->all(),
        ]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('dashboard.profile.update'), [
                'name' => $entertainer->name,
                'short_introduction' => $entertainer->short_introduction,
                'bio' => $entertainer->bio,
                'city' => $entertainer->city,
                'region' => $entertainer->region,
                'working_radius_km' => $entertainer->working_radius_km,
                'travel_free_km' => $entertainer->travel_free_km,
                'max_travel_distance_km' => $entertainer->max_travel_distance_km,
                'gallery_photos' => [
                    UploadedFile::fake()->image('gallery-12.jpg'),
                    UploadedFile::fake()->image('gallery-13.jpg'),
                ],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('gallery_photos');

        $this->assertCount(11, $entertainer->refresh()->gallery_photo_paths);
    }

    public function test_entertainer_cannot_manage_other_entertainer_data(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('entertainer');
        Entertainer::factory()->create(['user_id' => $user->id]);
        $otherEntertainer = Entertainer::factory()->create();
        $rate = Rate::factory()->create(['entertainer_id' => $otherEntertainer->id]);
        $availability = Availability::factory()->create(['entertainer_id' => $otherEntertainer->id]);

        $this->actingAs($user)
            ->patch(route('dashboard.rates.update', $rate), [
                'customer_type' => $rate->customer_type->value,
                'starting_rate_cents' => 99900,
                'hourly_rate_cents' => 7500,
                'minimum_hours' => 2,
                'travel_cost_cents_per_km' => 45,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('dashboard.availabilities.update', $availability), [
                'date' => now()->addWeek()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '16:00',
                'status' => AvailabilityStatus::Available->value,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_manage_everything(): void
    {
        $this->createRoles();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $entertainer = Entertainer::factory()->create();

        $this->actingAs($admin);

        $this->assertTrue($admin->can('update', $entertainer));
        $this->assertTrue($admin->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_admin_can_view_all_booking_requests_and_matches(): void
    {
        $this->createRoles();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $bookingRequest = BookingRequest::factory()->create(['entertainer_id' => null]);
        $match = BookingRequestMatch::query()->create([
            'booking_request_id' => $bookingRequest->id,
            'entertainer_id' => Entertainer::factory()->create()->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
        ]);

        $this->assertTrue($admin->can('viewAny', BookingRequest::class));
        $this->assertTrue($admin->can('view', $bookingRequest));
        $this->assertTrue($bookingRequest->matches()->whereKey($match->id)->exists());
    }

    public function test_admin_dashboard_signal_counts_are_calculated(): void
    {
        $skill = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $activeEntertainerWithoutRates = Entertainer::factory()->create([
            'active' => true,
            'profile_complete' => false,
            'profile_photo_path' => null,
        ]);
        $matchedEntertainer = Entertainer::factory()->create([
            'active' => true,
            'profile_photo_path' => 'profiles/demo.jpg',
        ]);
        $matchedEntertainer->skills()->attach($skill);
        Rate::factory()->create(['entertainer_id' => $matchedEntertainer->id]);

        BookingRequest::factory()->create([
            'skill_id' => $skill->id,
            'entertainer_id' => null,
            'status' => BookingStatus::New,
            'event_date' => now()->addMonth()->toDateString(),
        ]);

        $matchedRequest = BookingRequest::factory()->create([
            'skill_id' => $skill->id,
            'entertainer_id' => null,
            'status' => BookingStatus::New,
            'event_date' => now()->addMonth()->toDateString(),
        ]);

        BookingRequestMatch::query()->create([
            'booking_request_id' => $matchedRequest->id,
            'entertainer_id' => $matchedEntertainer->id,
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
        ]);

        BookingRequest::factory()->create([
            'skill_id' => $skill->id,
            'entertainer_id' => $activeEntertainerWithoutRates->id,
            'status' => BookingStatus::Option,
            'event_date' => now()->addDays(7)->toDateString(),
            'quote_accepted_at' => null,
        ]);

        $signals = app(AdminDashboardSignalService::class);

        $this->assertSame(1, $signals->newRequestsWithoutMatchesCount());
        $this->assertSame(1, $signals->expiringQuoteOptionsCount());
        $this->assertSame(1, $signals->entertainersWithoutRatesCount());
        $this->assertSame(1, $signals->incompleteProfilesCount());
        $this->assertSame(1, $signals->underSuppliedPopularSkillsCount());
    }

    public function test_admin_dashboard_renders_signal_widgets(): void
    {
        $this->createRoles();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Signalen')
            ->assertSee('Nieuwe aanvragen zonder match')
            ->assertSee('Populaire skills zonder genoeg beschikbaar aanbod');
    }

    public function test_confirmed_booking_reminder_uses_event_tomorrow_flag_without_quote_acceptance_timestamp(): void
    {
        Mail::fake();

        $bookingRequest = BookingRequest::factory()->create([
            'status' => BookingStatus::Confirmed,
            'event_date' => today()->addDay()->toDateString(),
            'quote_accepted_at' => null,
            'reminder_flags' => null,
        ]);

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        $this->assertTrue($bookingRequest->refresh()->reminder_flags['event_tomorrow'] ?? false);
        $this->assertFalse($bookingRequest->reminder_flags['quote_expiring'] ?? false);
    }

    public function test_review_link_is_sent_after_confirmed_booking_has_finished(): void
    {
        Mail::fake();

        $entertainer = Entertainer::factory()->create(['active' => true]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'status' => BookingStatus::Confirmed,
            'event_date' => now()->subDay()->toDateString(),
            'email' => 'marieke@example.com',
            'name' => 'Marieke Jansen',
        ]);

        $this->artisan('reviews:send-links')->assertSuccessful();

        $review = Review::query()->where('booking_request_id', $bookingRequest->id)->firstOrFail();

        $this->assertSame($entertainer->id, $review->entertainer_id);
        $this->assertNotNull($review->link_sent_at);

        Mail::assertSent(ReviewRequestMail::class, fn (ReviewRequestMail $mail): bool => $mail->review->is($review));
    }

    public function test_review_link_is_not_sent_for_already_submitted_review(): void
    {
        Mail::fake();

        $entertainer = Entertainer::factory()->create(['active' => true]);
        $bookingRequest = BookingRequest::factory()->create([
            'entertainer_id' => $entertainer->id,
            'status' => BookingStatus::Confirmed,
            'event_date' => now()->subDay()->toDateString(),
            'email' => 'marieke@example.com',
            'name' => 'Marieke Jansen',
        ]);
        $review = Review::factory()->create([
            'booking_request_id' => $bookingRequest->id,
            'entertainer_id' => $entertainer->id,
            'customer_email' => 'marieke@example.com',
            'submitted_at' => now(),
            'link_sent_at' => null,
        ]);

        $this->artisan('reviews:send-links')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($review->refresh()->link_sent_at);
    }

    public function test_customer_can_submit_review_from_token_link(): void
    {
        Mail::fake();
        $this->createRoles();
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('admin');
        $entertainerUser = User::factory()->create(['email' => 'entertainer@example.com']);
        $entertainer = Entertainer::factory()->create(['user_id' => $entertainerUser->id]);
        $review = Review::factory()->create([
            'entertainer_id' => $entertainer->id,
            'status' => ReviewStatus::Pending,
            'submitted_at' => null,
            'rating' => null,
            'body' => null,
        ]);

        $this->get(route('reviews.create', $review->token))
            ->assertOk()
            ->assertSee('Hoe was');

        $this->post(route('reviews.store', $review->token), [
            'rating' => 5,
            'title' => 'Fantastisch feest',
            'body' => 'De kinderen vonden het optreden fantastisch en alles was goed geregeld.',
        ])->assertRedirect(route('reviews.thanks'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'title' => 'Fantastisch feest',
            'status' => ReviewStatus::Pending->value,
        ]);
        $this->assertNotNull($review->refresh()->submitted_at);
        Mail::assertSentCount(2);
    }

    public function test_review_token_expires(): void
    {
        $review = Review::factory()->create([
            'token_expires_at' => now()->subMinute(),
            'submitted_at' => null,
        ]);

        $this->get(route('reviews.create', $review->token))->assertGone();
        $this->post(route('reviews.store', $review->token), [
            'rating' => 5,
            'body' => 'Deze review komt te laat binnen en mag niet meer worden opgeslagen.',
        ])->assertGone();
    }

    public function test_registration_sends_email_verification_notification(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'account_type' => 'klant',
            'name' => 'Nieuwe Klant',
            'email' => 'nieuwe-klant@example.com',
            'password' => 'veilig-wachtwoord',
            'password_confirmation' => 'veilig-wachtwoord',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'nieuwe-klant@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->post(route('password.email'), ['email' => 'reset@example.com'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_only_approved_published_reviews_are_visible_on_entertainer_profile(): void
    {
        $entertainer = Entertainer::factory()->create(['name' => 'Sanne Schminkster', 'slug' => 'sanne-schminkster', 'active' => true]);

        Review::factory()->create([
            'entertainer_id' => $entertainer->id,
            'customer_name' => 'Marieke',
            'rating' => 5,
            'title' => 'Geweldig',
            'body' => 'Een heel fijne ervaring voor alle kinderen.',
            'status' => ReviewStatus::Approved,
            'submitted_at' => now()->subDay(),
            'published_at' => now(),
        ]);
        Review::factory()->create([
            'entertainer_id' => $entertainer->id,
            'title' => 'Nog niet zichtbaar',
            'status' => ReviewStatus::Pending,
            'published_at' => null,
        ]);

        $this->get(route('entertainers.show', $entertainer))
            ->assertOk()
            ->assertSee('Geweldig')
            ->assertSee('5/5')
            ->assertDontSee('Nog niet zichtbaar');
    }

    public function test_entertainer_rating_is_cleared_when_last_approved_review_is_unpublished(): void
    {
        $entertainer = Entertainer::factory()->create([
            'rating' => null,
            'reviews_count' => 0,
        ]);
        $review = Review::factory()->create([
            'entertainer_id' => $entertainer->id,
            'rating' => 5,
            'status' => ReviewStatus::Approved,
            'submitted_at' => now()->subDay(),
            'published_at' => now(),
        ]);

        $this->assertSame(1, $entertainer->refresh()->reviews_count);
        $this->assertSame('5.0', $entertainer->rating);

        $review->update([
            'status' => ReviewStatus::Pending,
            'published_at' => null,
        ]);

        $this->assertSame(0, $entertainer->refresh()->reviews_count);
        $this->assertNull($entertainer->rating);
    }

    public function test_general_request_scores_matches_and_blocks_outside_working_area(): void
    {
        $schminker = Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']);
        $near = $this->availableEntertainerForSkill($schminker, [
            'name' => 'Utrechtse Artiest',
            'city' => 'Utrecht',
            'region' => 'Utrecht',
            'working_radius_km' => 60,
            'max_travel_distance_km' => 60,
            'rating' => 4.9,
            'reviews_count' => 20,
        ]);
        $far = $this->availableEntertainerForSkill($schminker, [
            'name' => 'Amsterdamse Artiest',
            'city' => 'Amsterdam',
            'region' => 'Noord-Holland',
            'working_radius_km' => 90,
            'max_travel_distance_km' => 10,
        ]);

        $this->post(route('booking-requests.general.store'), $this->bookingRequestPayload([
            'request_type' => 'general',
            'skill_id' => $schminker->id,
            'city' => 'Utrecht',
            'event_region' => 'Utrecht',
        ]))->assertRedirect(route('booking-requests.thanks'));

        $this->assertDatabaseHas('booking_request_matches', [
            'entertainer_id' => $near->id,
        ]);
        $this->assertDatabaseMissing('booking_request_matches', [
            'entertainer_id' => $far->id,
        ]);

        $match = BookingRequestMatch::where('entertainer_id', $near->id)->firstOrFail();

        $this->assertGreaterThan(0, $match->match_score);
        $this->assertNotNull($match->score_breakdown);
    }

    public function test_customer_can_message_cancel_and_manage_favorites(): void
    {
        $this->createRoles();
        $customer = User::factory()->create(['email' => 'marieke@example.com']);
        $customer->assignRole('klant');
        $entertainer = Entertainer::factory()->create(['active' => true]);
        $bookingRequest = BookingRequest::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'marieke@example.com',
            'entertainer_id' => $entertainer->id,
            'status' => BookingStatus::Option,
        ]);

        $this->actingAs($customer)
            ->post(route('customer-portal.favorites.store', $entertainer))
            ->assertRedirect();

        $this->assertTrue($customer->favoriteEntertainers()->whereKey($entertainer->id)->exists());

        $this->actingAs($customer)
            ->post(route('customer-portal.messages.store', $bookingRequest), [
                'body' => 'Kunnen we buiten starten?',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('booking_request_events', [
            'booking_request_id' => $bookingRequest->id,
            'body' => 'Kunnen we buiten starten?',
            'visible_to_customer' => true,
            'visible_to_entertainer' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('customer-portal.cancel', $bookingRequest), [
                'cancellation_reason' => 'Het feest gaat niet door.',
            ])
            ->assertRedirect();

        $bookingRequest->refresh();

        $this->assertSame(BookingStatus::Cancelled, $bookingRequest->status);
        $this->assertSame('customer', $bookingRequest->cancelled_by);
    }

    private function createRoles(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'entertainer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'klant', 'guard_name' => 'web']);
    }

    private function bookingRequestPayload(array $overrides = []): array
    {
        return [
            'request_type' => 'specific',
            'customer_type' => 'consument',
            'name' => 'Marieke Jansen',
            'email' => 'marieke@example.com',
            'phone' => '0612345678',
            'event_date' => now()->addWeeks(2)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'address' => 'Dorpsstraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Utrecht',
            'children_count' => 12,
            'desired_skills' => ['Schminker'],
            'message' => 'Graag beschikbaarheid checken.',
            ...$overrides,
        ];
    }

    private function availableEntertainerForSkill(Skill $skill, array $overrides = []): Entertainer
    {
        $entertainer = Entertainer::factory()->create(['active' => true, ...$overrides]);
        $entertainer->skills()->attach($skill);

        Availability::factory()->create([
            'entertainer_id' => $entertainer->id,
            'date' => now()->addWeeks(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '17:00',
            'status' => AvailabilityStatus::Available,
        ]);

        return $entertainer;
    }
}
