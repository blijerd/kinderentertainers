<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'users',
        'entertainers',
        'booking_requests',
        'booking_request_matches',
        'reviews',
        'availabilities',
        'availability_rules',
        'rates',
        'entertainer_integrations',
        'skills',
        'legal_documents',
        'legal_document_versions',
        'landing_pages',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (! Schema::hasColumn($table, 'public_id')) {
                    $blueprint->uuid('public_id')->nullable()->unique();
                }

                if (! Schema::hasColumn($table, 'deleted_at')) {
                    $blueprint->softDeletes();
                }
            });

            DB::table($table)->whereNull('public_id')->orderBy('id')->each(function (object $row) use ($table): void {
                DB::table($table)->where('id', $row->id)->update([
                    'public_id' => (string) Str::uuid(),
                ]);
            });

            DB::statement("ALTER TABLE {$table} ALTER COLUMN public_id SET NOT NULL");
        }

        Schema::table('rates', function (Blueprint $table): void {
            $table->dropUnique(['entertainer_id', 'customer_type']);
        });
        DB::statement('CREATE UNIQUE INDEX rates_entertainer_id_customer_type_unique ON rates (entertainer_id, customer_type) WHERE deleted_at IS NULL');

        Schema::table('entertainer_integrations', function (Blueprint $table): void {
            $table->dropUnique(['entertainer_id', 'provider']);
        });
        DB::statement('CREATE UNIQUE INDEX entertainer_integrations_entertainer_id_provider_unique ON entertainer_integrations (entertainer_id, provider) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (Schema::hasColumn($table, 'public_id')) {
                    $blueprint->dropUnique(['public_id']);
                    $blueprint->dropColumn('public_id');
                }

                if (Schema::hasColumn($table, 'deleted_at')) {
                    $blueprint->dropSoftDeletes();
                }
            });
        }
    }
};
