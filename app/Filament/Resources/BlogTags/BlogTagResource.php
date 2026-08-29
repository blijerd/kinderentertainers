<?php

namespace App\Filament\Resources\BlogTags;

use App\Filament\Resources\BlogTags\Pages\CreateBlogTag;
use App\Filament\Resources\BlogTags\Pages\EditBlogTag;
use App\Filament\Resources\BlogTags\Pages\ListBlogTags;
use App\Filament\Resources\BlogTags\Schemas\BlogTagForm;
use App\Filament\Resources\BlogTags\Tables\BlogTagsTable;
use App\Models\BlogTag;
use App\Support\Filament\Concerns\ResolvesPublicRecordRouteBinding;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BlogTagResource extends Resource
{
    use ResolvesPublicRecordRouteBinding;

    protected static ?string $model = BlogTag::class;

    protected static ?string $recordRouteKeyName = 'public_id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $modelLabel = 'blogtag';

    protected static ?string $pluralModelLabel = 'blogtags';

    protected static ?string $navigationLabel = 'Blogtags';

    protected static ?int $navigationSort = 41;

    public static function form(Schema $schema): Schema
    {
        return BlogTagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogTagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogTags::route('/'),
            'create' => CreateBlogTag::route('/create'),
            'edit' => EditBlogTag::route('/{record}/edit'),
        ];
    }
}
