<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entertainers', function (Blueprint $table): void {
            $table->json('packages')->nullable()->after('practical_requirements');
            $table->json('extras')->nullable()->after('packages');
            $table->text('cancellation_policy')->nullable()->after('extras');
            $table->unsignedTinyInteger('deposit_percentage')->default(0)->after('cancellation_policy');
            $table->unsignedTinyInteger('profile_quality_score')->default(0)->after('profile_complete');
            $table->unsignedSmallInteger('average_response_minutes')->nullable()->after('profile_quality_score');
        });

        Schema::table('booking_request_matches', function (Blueprint $table): void {
            $table->unsignedTinyInteger('match_score')->default(0)->after('status');
            $table->decimal('distance_km', 6, 1)->nullable()->after('match_score');
            $table->unsignedSmallInteger('travel_minutes')->nullable()->after('distance_km');
            $table->json('score_breakdown')->nullable()->after('travel_minutes');
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->string('selected_package')->nullable()->after('desired_skills');
            $table->json('selected_extras')->nullable()->after('selected_package');
            $table->unsignedInteger('deposit_cents')->nullable()->after('quote_total_cents');
            $table->unsignedInteger('paid_cents')->default(0)->after('deposit_cents');
            $table->string('payment_status')->default('open')->after('paid_cents');
            $table->string('payment_reference')->nullable()->after('payment_status');
            $table->timestamp('payment_due_at')->nullable()->after('payment_reference');
            $table->timestamp('agreement_accepted_at')->nullable()->after('quote_accepted_at');
            $table->string('agreement_version')->nullable()->after('agreement_accepted_at');
            $table->timestamp('cancelled_at')->nullable()->after('agreement_version');
            $table->string('cancelled_by')->nullable()->after('cancelled_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('cancellation_reason');
            $table->json('reminder_flags')->nullable()->after('last_reminder_sent_at');
        });

        Schema::table('booking_request_events', function (Blueprint $table): void {
            $table->boolean('visible_to_customer')->default(false)->after('visible_to_entertainer');
        });

        Schema::create('customer_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entertainer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'entertainer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_favorites');

        Schema::table('booking_request_events', function (Blueprint $table): void {
            $table->dropColumn('visible_to_customer');
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'selected_package',
                'selected_extras',
                'deposit_cents',
                'paid_cents',
                'payment_status',
                'payment_reference',
                'payment_due_at',
                'agreement_accepted_at',
                'agreement_version',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
                'last_reminder_sent_at',
                'reminder_flags',
            ]);
        });

        Schema::table('booking_request_matches', function (Blueprint $table): void {
            $table->dropColumn(['match_score', 'distance_km', 'travel_minutes', 'score_breakdown']);
        });

        Schema::table('entertainers', function (Blueprint $table): void {
            $table->dropColumn([
                'packages',
                'extras',
                'cancellation_policy',
                'deposit_percentage',
                'profile_quality_score',
                'average_response_minutes',
            ]);
        });
    }
};
