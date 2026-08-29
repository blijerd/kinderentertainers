<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_redirects', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('from_path');
            $table->string('to_url');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->string('source_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('from_path');
            $table->index(['is_active', 'from_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_redirects');
    }
};
