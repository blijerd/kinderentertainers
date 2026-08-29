<?php

namespace App\Filament\Resources\BlogTags\Pages;

use App\Actions\CreateBlogTag as CreateBlogTagAction;
use App\Filament\Resources\BlogTags\BlogTagResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBlogTag extends CreateRecord
{
    protected static string $resource = BlogTagResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateBlogTagAction::class)->handle($data);
    }
}
