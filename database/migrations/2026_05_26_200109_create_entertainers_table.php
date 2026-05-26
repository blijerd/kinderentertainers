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
        Schema::create('entertainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('profile_photo_path')->nullable();
            $table->string('short_introduction', 240);
            $table->text('bio')->nullable();
            $table->string('city');
            $table->string('region')->index();
            $table->unsignedSmallInteger('working_radius_km')->default(30);
            $table->boolean('active')->default(false)->index();
            $table->boolean('featured')->default(false)->index();
            $table->boolean('profile_complete')->default(false)->index();
            $table->timestamps();

            $table->index(['active', 'featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entertainers');
    }
};
