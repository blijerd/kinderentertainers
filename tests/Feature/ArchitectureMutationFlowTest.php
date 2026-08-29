<?php

namespace Tests\Feature;

use Tests\TestCase;

class ArchitectureMutationFlowTest extends TestCase
{
    public function test_dashboard_and_portal_controllers_delegate_mutations_to_actions(): void
    {
        $dashboard = (string) file_get_contents(app_path('Http/Controllers/EntertainerDashboardController.php'));
        $portal = (string) file_get_contents(app_path('Http/Controllers/CustomerPortalController.php'));
        $reviews = (string) file_get_contents(app_path('Http/Controllers/ReviewController.php'));
        $register = (string) file_get_contents(app_path('Http/Controllers/Auth/RegisteredUserController.php'));

        foreach ([
            'TransitionBookingRequestStatus',
            'RespondToBookingRequestMatch',
            'UpdateEntertainerProfile',
            'CreateBookingQuote',
        ] as $action) {
            $this->assertStringContainsString($action, $dashboard);
        }

        $this->assertStringContainsString('CancelBookingRequest', $portal);
        $this->assertStringContainsString('UpdateBookingRequestDetails', $portal);
        $this->assertStringContainsString('SubmitReview', $reviews);
        $this->assertStringContainsString('RegisterAccount', $register);

        $this->assertStringNotContainsString('CalendarIntegrationService', $dashboard);
        $this->assertStringNotContainsString('CalendarIntegrationService', $portal);
        $this->assertStringNotContainsString('$bookingRequest->update(', $dashboard);
        $this->assertStringNotContainsString('$bookingRequest->update(', $portal);
        $this->assertStringNotContainsString('$review->update(', $reviews);
        $this->assertStringNotContainsString('Entertainer::query()->create', $register);
    }

    public function test_filament_booking_pages_use_dedicated_actions(): void
    {
        $create = (string) file_get_contents(app_path('Filament/Resources/BookingRequests/Pages/CreateBookingRequest.php'));
        $edit = (string) file_get_contents(app_path('Filament/Resources/BookingRequests/Pages/EditBookingRequest.php'));
        $form = (string) file_get_contents(app_path('Filament/Resources/BookingRequests/Schemas/BookingRequestForm.php'));

        $this->assertStringContainsString('CreateBookingRequestAction::class', $create);
        $this->assertStringContainsString('UpdateBookingRequestDetails::class', $edit);
        $this->assertStringContainsString('TransitionBookingRequestStatus::class', $edit);
        $this->assertStringContainsString('->dehydrated(false)', $form);
    }

    public function test_filament_blog_pages_use_dedicated_actions(): void
    {
        $createPost = (string) file_get_contents(app_path('Filament/Resources/BlogPosts/Pages/CreateBlogPost.php'));
        $editPost = (string) file_get_contents(app_path('Filament/Resources/BlogPosts/Pages/EditBlogPost.php'));
        $createTag = (string) file_get_contents(app_path('Filament/Resources/BlogTags/Pages/CreateBlogTag.php'));
        $editTag = (string) file_get_contents(app_path('Filament/Resources/BlogTags/Pages/EditBlogTag.php'));

        $this->assertStringContainsString('CreateBlogPostAction::class', $createPost);
        $this->assertStringContainsString('UpdateBlogPost::class', $editPost);
        $this->assertStringContainsString('DeleteBlogPost::class', $editPost);
        $this->assertStringContainsString('CreateBlogTagAction::class', $createTag);
        $this->assertStringContainsString('UpdateBlogTag::class', $editTag);
        $this->assertStringContainsString('DeleteBlogTag::class', $editTag);
    }

    public function test_filament_landing_pages_use_dedicated_actions(): void
    {
        $create = (string) file_get_contents(app_path('Filament/Resources/LandingPages/Pages/CreateLandingPage.php'));
        $edit = (string) file_get_contents(app_path('Filament/Resources/LandingPages/Pages/EditLandingPage.php'));

        $this->assertStringContainsString('UpsertLandingPage::class', $create);
        $this->assertStringContainsString('UpsertLandingPage::class', $edit);
    }

    public function test_content_cli_commands_delegate_to_actions(): void
    {
        $commands = [
            'ContentSyncCommand.php' => 'SyncRepositoryContent',
            'ContentUpsertPageCommand.php' => 'UpsertLandingPage',
            'ContentUpsertBlogCommand.php' => 'UpsertBlogPost',
            'ContentImportMediaCommand.php' => 'ImportContentMedia',
            'ContentUpsertRedirectCommand.php' => 'UpsertContentRedirect',
        ];

        foreach ($commands as $file => $action) {
            $source = (string) file_get_contents(app_path('Console/Commands/'.$file));
            $this->assertStringContainsString($action, $source, $file);
        }
    }

    public function test_ui_layers_do_not_call_integration_services_directly(): void
    {
        $violations = $this->scanUiLayerFilesFor([
            'CalendarIntegrationService',
            'PaymentCheckoutService',
            'InvoiceIntegrationService',
            'Mail::to(',
        ]);

        $allowed = [
            'app/Http/Controllers/Webhooks/PaymentWebhookController.php',
        ];

        $violations = array_values(array_filter(
            $violations,
            fn (string $path): bool => ! in_array($path, $allowed, true),
        ));

        $this->assertSame([], $violations, 'UI layers must not call integration services directly: '.implode(', ', $violations));
    }

    public function test_filament_resources_reject_numeric_record_keys(): void
    {
        $resources = [
            'BookingRequests/BookingRequestResource.php',
            'Entertainers/EntertainerResource.php',
            'Reviews/ReviewResource.php',
            'Users/UserResource.php',
            'BlogPosts/BlogPostResource.php',
            'BlogTags/BlogTagResource.php',
            'LandingPages/LandingPageResource.php',
        ];

        foreach ($resources as $resource) {
            $source = (string) file_get_contents(app_path('Filament/Resources/'.$resource));
            $this->assertStringContainsString('ResolvesPublicRecordRouteBinding', $source, $resource);
        }
    }

    /**
     * @param  list<string>  $needles
     * @return list<string>
     */
    protected function scanUiLayerFilesFor(array $needles): array
    {
        $violations = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path(), \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace(base_path().'/', '', $file->getPathname());

            if (! str_contains($path, '/Livewire/')
                && ! str_contains($path, '/Filament/')
                && ! str_contains($path, '/Http/')) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());

            foreach ($needles as $needle) {
                if (str_contains($source, $needle)) {
                    $violations[] = $path;
                    break;
                }
            }
        }

        return $violations;
    }
}
