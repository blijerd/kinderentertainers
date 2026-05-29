<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->timestamp('customer_selection_expires_at')->nullable()->after('customer_selection_token');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->timestamp('token_expires_at')->nullable()->after('token');
        });

        Schema::table('entertainers', function (Blueprint $table): void {
            $table->timestamp('publication_requested_at')->nullable()->after('profile_quality_score');
            $table->timestamp('publication_reviewed_at')->nullable()->after('publication_requested_at');
            $table->text('publication_review_note')->nullable()->after('publication_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('entertainers', function (Blueprint $table): void {
            $table->dropColumn([
                'publication_requested_at',
                'publication_reviewed_at',
                'publication_review_note',
            ]);
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn('token_expires_at');
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn('customer_selection_expires_at');
        });
    }
};
