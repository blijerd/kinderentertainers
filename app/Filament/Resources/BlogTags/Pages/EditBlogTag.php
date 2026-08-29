<?php

namespace App\Filament\Resources\BlogTags\Pages;

use App\Actions\DeleteBlogTag;
use App\Actions\UpdateBlogTag;
use App\Filament\Resources\BlogTags\BlogTagResource;
use App\Models\BlogTag;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBlogTag extends EditRecord
{
    protected static string $resource = BlogTagResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var BlogTag $record */
        return app(UpdateBlogTag::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function (BlogTag $record): bool {
                    app(DeleteBlogTag::class)->handle($record);

                    return true;
                }),
        ];
    }
}
