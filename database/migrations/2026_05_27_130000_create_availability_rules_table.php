<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('entertainer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('rule_type')->default('weekly')->index();
            $table->json('weekdays')->nullable();
            $table->date('starts_on')->index();
            $table->date('ends_on')->nullable()->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('beschikbaar')->index();
            $table->text('internal_note')->nullable();
            $table->timestamps();

            $table->index(['entertainer_id', 'rule_type', 'status']);
            $table->index(['entertainer_id', 'starts_on', 'ends_on'], 'availability_rules_entertainer_date_range_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_rules');
    }
};
