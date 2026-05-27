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
            $table->json('gallery_photo_paths')->nullable()->after('profile_photo_path');
            $table->json('profile_highlights')->nullable()->after('bio');
            $table->string('audience_age_range')->nullable()->after('profile_highlights');
            $table->unsignedSmallInteger('performance_duration_minutes')->nullable()->after('audience_age_range');
            $table->unsignedSmallInteger('setup_time_minutes')->nullable()->after('performance_duration_minutes');
            $table->string('show_reel_url')->nullable()->after('setup_time_minutes');
            $table->text('practical_requirements')->nullable()->after('show_reel_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entertainers', function (Blueprint $table) {
            $table->dropColumn([
                'gallery_photo_paths',
                'profile_highlights',
                'audience_age_range',
                'performance_duration_minutes',
                'setup_time_minutes',
                'show_reel_url',
                'practical_requirements',
            ]);
        });
    }
};
