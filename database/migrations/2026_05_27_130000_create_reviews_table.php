<?php

use App\Enums\ReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('entertainer_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_email')->index();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->default(ReviewStatus::Pending->value)->index();
            $table->string('token', 80)->unique();
            $table->timestamp('link_sent_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['entertainer_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
