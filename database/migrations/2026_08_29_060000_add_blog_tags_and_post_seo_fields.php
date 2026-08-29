<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('blog_posts', 'author_id')) {
                $table->foreignId('author_id')->nullable()->after('public_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('blog_posts', 'og_image_path')) {
                $table->string('og_image_path')->nullable()->after('cover_image_path');
            }

            $table->index(['is_published', 'published_at']);
        });

        Schema::create('blog_tags', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('blog_post_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blog_post_id', 'blog_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_tag');
        Schema::dropIfExists('blog_tags');

        Schema::table('blog_posts', function (Blueprint $table): void {
            if (Schema::hasColumn('blog_posts', 'author_id')) {
                $table->dropConstrainedForeignId('author_id');
            }

            if (Schema::hasColumn('blog_posts', 'og_image_path')) {
                $table->dropColumn('og_image_path');
            }

            $table->dropIndex(['is_published', 'published_at']);
        });
    }
};
