<?php

namespace Tests\Feature;

use App\Actions\CheckEntertainerAvailability;
use App\Enums\AvailabilityStatus;
use App\Enums\BookingStatus;
use App\Models\Availability;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_booking_request_can_be_created(): void
    {
        $entertainer = Entertainer::factory()->create(['active' => true, 'slug' => 'sanne']);
        $entertainer->skills()->attach(Skill::factory()->create(['name' => 'Schminker', 'slug' => 'schminker']));

        $this->post(route('booking-requests.store', $entertainer), [
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
        ])->assertRedirect(route('booking-requests.thanks'));

        $this->assertDatabaseHas('booking_requests', [
            'entertainer_id' => $entertainer->id,
            'email' => 'marieke@example.com',
            'status' => BookingStatus::New->value,
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
}
