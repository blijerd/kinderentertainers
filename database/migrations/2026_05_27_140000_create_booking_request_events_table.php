<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_request_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('actor_type')->index();
            $table->string('actor_name')->nullable();
            $table->text('body')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->boolean('visible_to_entertainer')->default(true)->index();
            $table->timestamps();

            $table->index(['booking_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_request_events');
    }
};
