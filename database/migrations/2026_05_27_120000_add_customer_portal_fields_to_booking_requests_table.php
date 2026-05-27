<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('booking_requests', 'customer_id')) {
            Schema::table('booking_requests', function (Blueprint $table): void {
                $table->foreignId('customer_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('booking_requests', 'quote_accepted_at')) {
            Schema::table('booking_requests', function (Blueprint $table): void {
                $table->timestamp('quote_accepted_at')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('booking_requests', 'customer_message')) {
            Schema::table('booking_requests', function (Blueprint $table): void {
                $table->text('customer_message')->nullable()->after('internal_note');
            });
        }

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->index(['customer_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropIndex(['customer_id', 'event_date']);
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['customer_message']);
        });
    }
};
