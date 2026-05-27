<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_requests', 'customer_selection_token')) {
                $table->string('customer_selection_token', 64)->nullable()->unique()->after('internal_note');
            }
        });

        Schema::table('booking_request_matches', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_request_matches', 'price_indication_cents')) {
                $table->unsignedInteger('price_indication_cents')->nullable()->after('status');
            }

            if (! Schema::hasColumn('booking_request_matches', 'response_message')) {
                $table->text('response_message')->nullable()->after('price_indication_cents');
            }

            if (! Schema::hasColumn('booking_request_matches', 'selected_at')) {
                $table->timestamp('selected_at')->nullable()->after('responded_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_request_matches', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_request_matches', 'selected_at')) {
                $table->dropColumn('selected_at');
            }

            if (Schema::hasColumn('booking_request_matches', 'response_message')) {
                $table->dropColumn('response_message');
            }

            if (Schema::hasColumn('booking_request_matches', 'price_indication_cents')) {
                $table->dropColumn('price_indication_cents');
            }
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_requests', 'customer_selection_token')) {
                $table->dropColumn('customer_selection_token');
            }
        });
    }
};
