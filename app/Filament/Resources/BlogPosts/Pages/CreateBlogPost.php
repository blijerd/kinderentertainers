<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Actions\CreateBlogPost as CreateBlogPostAction;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateBlogPostAction::class)->handle($data);
    }
}
