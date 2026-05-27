<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->unsignedInteger('quote_performance_cents')->nullable()->after('internal_note');
            $table->unsignedInteger('quote_travel_cents')->nullable()->after('quote_performance_cents');
            $table->unsignedInteger('quote_total_cents')->nullable()->after('quote_travel_cents');
            $table->decimal('quote_travel_distance_km', 6, 1)->nullable()->after('quote_total_cents');
            $table->timestamp('quote_valid_until')->nullable()->after('quote_travel_distance_km');
            $table->timestamp('quote_sent_at')->nullable()->after('quote_valid_until');
            $table->string('quote_acceptance_token', 64)->nullable()->unique()->after('quote_sent_at');
            $table->string('quote_terms_version')->nullable()->after('quote_acceptance_token');
            $table->longText('quote_terms_body')->nullable()->after('quote_terms_version');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropUnique(['quote_acceptance_token']);
            $table->dropColumn([
                'quote_performance_cents',
                'quote_travel_cents',
                'quote_total_cents',
                'quote_travel_distance_km',
                'quote_valid_until',
                'quote_sent_at',
                'quote_acceptance_token',
                'quote_terms_version',
                'quote_terms_body',
            ]);
        });
    }
};
