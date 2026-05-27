<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->string('event_region')->nullable()->after('city');
            $table->unsignedSmallInteger('travel_time_minutes')->nullable()->after('event_region');
            $table->unsignedInteger('price_indication_min_cents')->nullable()->after('internal_note');
            $table->unsignedInteger('price_indication_max_cents')->nullable()->after('price_indication_min_cents');
            $table->string('price_indication_currency', 3)->default('EUR')->after('price_indication_max_cents');
            $table->json('price_indication_breakdown')->nullable()->after('price_indication_currency');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'event_region',
                'travel_time_minutes',
                'price_indication_min_cents',
                'price_indication_max_cents',
                'price_indication_currency',
                'price_indication_breakdown',
            ]);
        });
    }
};
