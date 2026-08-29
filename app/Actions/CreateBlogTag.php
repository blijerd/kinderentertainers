<?php

namespace App\Actions;

use App\Actions\Concerns\PreparesBlogTagData;
use App\Models\BlogTag;

class CreateBlogTag
{
    use PreparesBlogTagData;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): BlogTag
    {
        return BlogTag::query()->create($this->prepareBlogTagData($data));
    }
}
