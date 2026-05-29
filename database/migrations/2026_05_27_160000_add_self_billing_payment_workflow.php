<?php

use App\Enums\IntegrationProvider;
use App\Enums\PaymentProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entertainers', function (Blueprint $table): void {
            $table->string('payment_provider')->default(PaymentProvider::Manual->value)->after('accounting_notes')->index();
            $table->boolean('cash_payment_enabled')->default(false)->after('payment_provider');
            $table->text('payment_notes')->nullable()->after('cash_payment_enabled');
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->string('invoice_status')->default('not_started')->after('payment_due_at');
            $table->string('invoice_provider')->nullable()->after('invoice_status');
            $table->string('invoice_reference')->nullable()->after('invoice_provider');
            $table->string('invoice_url')->nullable()->after('invoice_reference');
            $table->string('payment_provider')->nullable()->after('invoice_url');
            $table->string('payment_checkout_url')->nullable()->after('payment_provider');
            $table->boolean('cash_payment_allowed')->default(false)->after('payment_checkout_url');
            $table->timestamp('payment_instruction_sent_at')->nullable()->after('cash_payment_allowed');
        });

        foreach (DB::table('entertainers')->pluck('id') as $entertainerId) {
            foreach (IntegrationProvider::cases() as $provider) {
                DB::table('entertainer_integrations')->updateOrInsert(
                    [
                        'entertainer_id' => $entertainerId,
                        'provider' => $provider->value,
                    ],
                    [
                        'enabled' => false,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'invoice_status',
                'invoice_provider',
                'invoice_reference',
                'invoice_url',
                'payment_provider',
                'payment_checkout_url',
                'cash_payment_allowed',
                'payment_instruction_sent_at',
            ]);
        });

        Schema::table('entertainers', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_provider',
                'cash_payment_enabled',
                'payment_notes',
            ]);
        });
    }
};
