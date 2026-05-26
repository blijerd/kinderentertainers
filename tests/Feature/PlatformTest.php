<?php

namespace Tests\Feature;

use App\Actions\CheckEntertainerAvailability;
use App\Actions\FindAvailableEntertainersForRequest;
use App\Enums\AvailabilityStatus;
use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use App\Models\Availability;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use App\Models\Rate;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $bookingRequest = BookingRequest::factory()->create(['entertainer_id' => $entertainer->id]);

        $this->actingAs($user)
            ->patch(route('dashboard.booking-requests.status', $bookingRequest), ['status' => 'bevestigd'])
            ->assertRedirect();

        $this->assertDatabaseHas('booking_requests', [
            'id' => $bookingRequest->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
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

    private function createRoles(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'entertainer', 'guard_name' => 'web']);
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
