<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('booking_requests', 'skill_id')) {
            Schema::table('booking_requests', function (Blueprint $table): void {
                $table->foreignId('skill_id')->nullable()->after('entertainer_id')->constrained()->nullOnDelete();
                $table->index(['skill_id', 'status']);
            });
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('booking_requests', function (Blueprint $table): void {
                $table->dropForeign(['entertainer_id']);
            });

            DB::statement('ALTER TABLE booking_requests MODIFY entertainer_id BIGINT UNSIGNED NULL');

            Schema::table('booking_requests', function (Blueprint $table): void {
                $table->foreign('entertainer_id')->references('id')->on('entertainers')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('booking_request_matches')) {
            Schema::create('booking_request_matches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('entertainer_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('beschikbaar')->index();
                $table->timestamp('matched_at')->useCurrent();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();

                $table->unique(['booking_request_id', 'entertainer_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_request_matches');

        if (Schema::hasColumn('booking_requests', 'skill_id')) {
            Schema::table('booking_requests', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('skill_id');
            });
        }
    }
};
