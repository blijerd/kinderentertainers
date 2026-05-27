<?php

use App\Enums\AccountingProvider;
use App\Enums\IntegrationProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entertainers', function (Blueprint $table): void {
            $table->string('accounting_provider')->default(AccountingProvider::Manual->value)->after('working_radius_km')->index();
            $table->text('accounting_notes')->nullable()->after('accounting_provider');
        });

        Schema::create('entertainer_integrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('entertainer_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->boolean('enabled')->default(false)->index();
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_check_status')->nullable();
            $table->text('last_check_message')->nullable();
            $table->timestamps();

            $table->unique(['entertainer_id', 'provider']);
        });

        foreach (DB::table('entertainers')->pluck('id') as $entertainerId) {
            foreach (IntegrationProvider::cases() as $provider) {
                DB::table('entertainer_integrations')->insert([
                    'entertainer_id' => $entertainerId,
                    'provider' => $provider->value,
                    'enabled' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entertainer_integrations');

        Schema::table('entertainers', function (Blueprint $table): void {
            $table->dropColumn(['accounting_provider', 'accounting_notes']);
        });
    }
};
