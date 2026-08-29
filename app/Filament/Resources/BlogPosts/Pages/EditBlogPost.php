<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Actions\DeleteBlogPost;
use App\Actions\UpdateBlogPost;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Models\BlogPost;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['tag_ids'] = $this->getRecord()->tags()->pluck('blog_tags.id')->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var BlogPost $record */
        return app(UpdateBlogPost::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function (BlogPost $record): bool {
                    app(DeleteBlogPost::class)->handle($record);

                    return true;
                }),
        ];
    }
}
