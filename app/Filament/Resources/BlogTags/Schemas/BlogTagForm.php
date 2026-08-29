<?php

namespace App\Filament\Resources\BlogTags\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogTagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Publieke URL: /blog/tag/jouw-slug. Leeg laten om automatisch te genereren.')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Beschrijving')
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull(),
                TextInput::make('seo_title')->label('SEO titel')->maxLength(255)->columnSpanFull(),
                Textarea::make('meta_description')->label('Meta description')->rows(3)->maxLength(320)->columnSpanFull(),
                Toggle::make('noindex')->label('Niet indexeren')->default(false),
            ]);
    }
}
