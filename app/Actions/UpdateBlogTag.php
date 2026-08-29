<?php

namespace App\Actions;

use App\Actions\Concerns\PreparesBlogTagData;
use App\Models\BlogTag;

class UpdateBlogTag
{
    use PreparesBlogTagData;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BlogTag $tag, array $data): BlogTag
    {
        $tag->update($this->prepareBlogTagData($data, $tag));

        return $tag->refresh();
    }
}
