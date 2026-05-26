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
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entertainer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_type')->index();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->index();
            $table->string('phone');
            $table->date('event_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('address');
            $table->string('postal_code', 16);
            $table->string('city');
            $table->unsignedSmallInteger('children_count')->nullable();
            $table->string('children_ages')->nullable();
            $table->json('desired_skills')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('nieuw')->index();
            $table->text('internal_note')->nullable();
            $table->timestamps();

            $table->index(['entertainer_id', 'status']);
            $table->index(['skill_id', 'status']);
            $table->index(['entertainer_id', 'event_date', 'start_time', 'end_time'], 'booking_requests_entertainer_datetime_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
