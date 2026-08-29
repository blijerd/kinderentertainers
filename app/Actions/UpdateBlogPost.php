<?php

namespace App\Actions;

use App\Actions\Concerns\PreparesBlogPostData;
use App\Models\BlogPost;
use Illuminate\Support\Facades\DB;

class UpdateBlogPost
{
    use PreparesBlogPostData;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BlogPost $post, array $data): BlogPost
    {
        [$payload, $tagIds] = $this->prepareBlogPostData($data, $post);

        return DB::transaction(function () use ($post, $payload, $tagIds): BlogPost {
            $post->update($payload);
            $post->tags()->sync($tagIds);

            return $post->refresh()->load(['tags', 'author']);
        });
    }
}
