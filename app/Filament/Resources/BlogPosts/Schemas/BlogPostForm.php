<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Models\BlogTag;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titel / H1')
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
                    ->helperText('Publieke URL: /blog/jouw-slug. Leeg laten om automatisch te genereren.')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('author_id')
                    ->label('Auteur')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->default(fn (): ?int => auth()->id()),
                Select::make('tag_ids')
                    ->label('Tags')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn (): array => BlogTag::query()->orderBy('name')->pluck('name', 'id')->all()),
                Textarea::make('intro')
                    ->label('Intro')
                    ->helperText('Korte intro op het overzicht en als fallback voor de meta description.')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
                Textarea::make('body')
                    ->label('Content')
                    ->helperText('Markdown wordt ondersteund, zoals ## tussenkoppen, lijsten en links.')
                    ->rows(16)
                    ->columnSpanFull(),
                FileUpload::make('cover_image_path')
                    ->label('Coverfoto')
                    ->image()
                    ->disk('public')
                    ->directory('blog/covers')
                    ->columnSpanFull(),
                Toggle::make('is_published')->label('Gepubliceerd')->default(false),
                DateTimePicker::make('published_at')->label('Publiceren vanaf'),
                TextInput::make('seo_title')->label('SEO titel')->maxLength(255)->columnSpanFull(),
                Textarea::make('meta_description')->label('Meta description')->rows(3)->maxLength(320)->columnSpanFull(),
                TextInput::make('canonical_url')->label('Canonical URL')->url()->maxLength(255)->columnSpanFull(),
                FileUpload::make('og_image_path')
                    ->label('Open Graph afbeelding')
                    ->image()
                    ->disk('public')
                    ->directory('blog/og-images')
                    ->columnSpanFull(),
                Toggle::make('noindex')->label('Niet indexeren')->default(false),
            ]);
    }
}
