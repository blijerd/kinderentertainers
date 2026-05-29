<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->string('invoice_external_id')->nullable()->after('invoice_reference');
            $table->string('payment_external_id')->nullable()->after('payment_reference');
            $table->string('calendar_external_id')->nullable()->after('calendar_synced_at');
            $table->timestamp('last_notification_sent_at')->nullable()->after('last_reminder_sent_at');
            $table->string('last_notification_status')->nullable()->after('last_notification_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'invoice_external_id',
                'payment_external_id',
                'calendar_external_id',
                'last_notification_sent_at',
                'last_notification_status',
            ]);
        });
    }
};
