<?php

namespace App\Actions;

use App\Actions\Concerns\PreparesBlogPostData;
use App\Models\BlogPost;
use Illuminate\Support\Facades\DB;

class CreateBlogPost
{
    use PreparesBlogPostData;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): BlogPost
    {
        [$payload, $tagIds] = $this->prepareBlogPostData($data);

        return DB::transaction(function () use ($payload, $tagIds): BlogPost {
            $post = BlogPost::query()->create($payload);
            $post->tags()->sync($tagIds);

            return $post->refresh()->load(['tags', 'author']);
        });
    }
}
