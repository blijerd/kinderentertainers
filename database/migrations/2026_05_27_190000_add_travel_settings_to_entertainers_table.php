<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entertainers', function (Blueprint $table): void {
            $table->unsignedSmallInteger('travel_free_km')->default(0)->after('working_radius_km');
            $table->unsignedSmallInteger('max_travel_distance_km')->nullable()->after('travel_free_km');
        });
    }

    public function down(): void
    {
        Schema::table('entertainers', function (Blueprint $table): void {
            $table->dropColumn([
                'travel_free_km',
                'max_travel_distance_km',
            ]);
        });
    }
};
