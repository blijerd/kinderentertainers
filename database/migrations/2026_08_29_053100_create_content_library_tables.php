<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table): void {
            if (! Schema::hasColumn('landing_pages', 'source_path')) {
                $table->string('source_path')->nullable()->after('og_image_path');
            }
        });

        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('noindex')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('source_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('content_media', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('original_filename');
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('byte_size')->default(0);
            $table->string('checksum', 64);
            $table->string('alt_text')->nullable();
            $table->string('source_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('checksum');
            $table->index('source_path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_media');
        Schema::dropIfExists('blog_posts');

        Schema::table('landing_pages', function (Blueprint $table): void {
            if (Schema::hasColumn('landing_pages', 'source_path')) {
                $table->dropColumn('source_path');
            }
        });
    }
};
