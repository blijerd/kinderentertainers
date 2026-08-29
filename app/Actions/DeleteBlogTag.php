<?php

namespace App\Actions;

use App\Models\BlogTag;

class DeleteBlogTag
{
    public function handle(BlogTag $tag): void
    {
        $tag->delete();
    }
}
