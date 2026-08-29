<?php

namespace Tests\Feature;

use App\Actions\CancelBookingRequest;
use App\Actions\TransitionBookingRequestStatus;
use App\Actions\UpdateBookingRequestDetails;
use App\Enums\BookingStatus;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\BookingRequest;
use App\Models\BookingRequestMatch;
use App\Models\Entertainer;
use App\Models\Review;
use App\Models\User;
use App\Support\Models\HasPublicIdentifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArchitectureReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_models_support_soft_deletes(): void
    {
        $user = User::factory()->create();
        $entertainer = Entertainer::factory()->create();
        $bookingRequest = BookingRequest::factory()->create();
        $review = Review::factory()->create();
        $blogPost = BlogPost::factory()->create();
        $blogTag = BlogTag::factory()->create();

        $user->delete();
        $entertainer->delete();
        $bookingRequest->delete();
        $review->delete();
        $blogPost->delete();
        $blogTag->delete();

        $this->assertSoftDeleted($user);
        $this->assertSoftDeleted($entertainer);
        $this->assertSoftDeleted($bookingRequest);
        $this->assertSoftDeleted($review);
        $this->assertSoftDeleted($blogPost);
        $this->assertSoftDeleted($blogTag);
    }

    public function test_public_identifiers_are_generated_uniquely(): void
    {
        $first = BookingRequest::factory()->create();
        $second = BookingRequest::factory()->create();

        $this->assertNotNull($first->public_id);
        $this->assertNotNull($second->public_id);
        $this->assertNotSame($first->public_id, $second->public_id);
        $this->assertSame('public_id', $first->getRouteKeyName());
    }

    public function test_customer_selection_url_does_not_expose_database_id(): void
    {
        $bookingRequest = BookingRequest::factory()->create([
            'customer_selection_token' => 'selection-token-example',
        ]);

        $url = $bookingRequest->customerSelectionUrl();

        $this->assertNotNull($url);
        $this->assertStringContainsString($bookingRequest->public_id, $url);
        $this->assertStringNotContainsString('/aanvragen/'.$bookingRequest->getKey().'/', $url);
    }

    public function test_core_models_use_the_public_identifier_trait(): void
    {
        foreach ([User::class, Entertainer::class, BookingRequest::class, Review::class, BlogPost::class, BlogTag::class] as $model) {
            $this->assertContains(HasPublicIdentifier::class, class_uses_recursive($model));
        }
    }

    public function test_booking_request_status_is_not_mass_assignable(): void
    {
        $this->assertNotContains('status', (new BookingRequest)->getFillable());
        $this->assertNotContains('status', (new BookingRequestMatch)->getFillable());
        $this->assertNotContains('status', (new Review)->getFillable());
    }

    public function test_generic_update_action_rejects_status_mutations(): void
    {
        $bookingRequest = BookingRequest::factory()->create([
            'status' => BookingStatus::New,
        ]);

        try {
            app(UpdateBookingRequestDetails::class)->handle($bookingRequest, [
                'status' => BookingStatus::Confirmed->value,
            ]);
            $this->fail('Expected the status mutation to be rejected outside the dedicated action layer.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->assertSame(BookingStatus::New, $bookingRequest->refresh()->status);
    }

    public function test_status_transitions_go_through_dedicated_actions(): void
    {
        $bookingRequest = BookingRequest::factory()->create([
            'status' => BookingStatus::New,
        ]);

        app(TransitionBookingRequestStatus::class)->handle($bookingRequest, BookingStatus::Confirmed);

        $this->assertSame(BookingStatus::Confirmed, $bookingRequest->refresh()->status);

        app(CancelBookingRequest::class)->handle($bookingRequest->refresh(), 'Testannulering', 'admin');

        $this->assertSame(BookingStatus::Cancelled, $bookingRequest->refresh()->status);
        $this->assertNotNull($bookingRequest->cancelled_at);
    }

    public function test_composer_declares_pgsql_and_mbstring_extensions(): void
    {
        $composer = json_decode((string) File::get(base_path('composer.json')), true);

        $this->assertSame('*', $composer['require']['ext-mbstring'] ?? null);
        $this->assertSame('*', $composer['require']['ext-pdo_pgsql'] ?? null);
    }
}
