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
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entertainer_id')->constrained()->cascadeOnDelete();
            $table->string('customer_type');
            $table->unsignedInteger('starting_rate_cents');
            $table->unsignedInteger('hourly_rate_cents');
            $table->decimal('minimum_hours', 3, 1)->default(1);
            $table->unsignedInteger('travel_cost_cents_per_km')->default(0);
            $table->boolean('vat_included')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['entertainer_id', 'customer_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
