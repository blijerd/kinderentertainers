<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->string('quote_acceptance_name')->nullable()->after('quote_accepted_at');
            $table->string('quote_acceptance_ip')->nullable()->after('quote_acceptance_name');
            $table->text('quote_acceptance_user_agent')->nullable()->after('quote_acceptance_ip');
            $table->string('agreement_hash', 64)->nullable()->after('agreement_version');
            $table->timestamp('invoice_generated_at')->nullable()->after('invoice_url');
            $table->timestamp('payment_checkout_created_at')->nullable()->after('payment_checkout_url');
            $table->timestamp('calendar_synced_at')->nullable()->after('payment_instruction_sent_at');
            $table->string('calendar_sync_status')->nullable()->after('calendar_synced_at');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->string('submission_ip')->nullable()->after('published_at');
            $table->text('submission_user_agent')->nullable()->after('submission_ip');
            $table->string('moderation_note')->nullable()->after('submission_user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn(['submission_ip', 'submission_user_agent', 'moderation_note']);
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'quote_acceptance_name',
                'quote_acceptance_ip',
                'quote_acceptance_user_agent',
                'agreement_hash',
                'invoice_generated_at',
                'payment_checkout_created_at',
                'calendar_synced_at',
                'calendar_sync_status',
            ]);
        });
    }
};
