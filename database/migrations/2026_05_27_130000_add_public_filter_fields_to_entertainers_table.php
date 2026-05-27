<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('entertainers', function (Blueprint $table) {
            $table->json('event_types')->nullable()->after('audience_age_range');
            $table->json('languages')->nullable()->after('event_types');
            $table->decimal('rating', 2, 1)->nullable()->after('languages');
            $table->unsignedInteger('reviews_count')->default(0)->after('rating');

            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entertainers', function (Blueprint $table) {
            $table->dropIndex(['rating']);
            $table->dropColumn([
                'event_types',
                'languages',
                'rating',
                'reviews_count',
            ]);
        });
    }
};
