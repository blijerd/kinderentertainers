<?php

namespace App\Actions;

use App\Models\BlogPost;

class DeleteBlogPost
{
    public function handle(BlogPost $post): void
    {
        $post->delete();
    }
}
