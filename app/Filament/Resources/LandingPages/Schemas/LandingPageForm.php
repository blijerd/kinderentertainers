<?php

namespace App\Filament\Resources\LandingPages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LandingPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Titel / H1')->required()->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Publieke URL: /jouw-slug')
                    ->required()
                    ->maxLength(255),
                Textarea::make('intro')->label('Intro')->rows(3)->maxLength(500)->columnSpanFull(),
                Textarea::make('body')
                    ->label('Content')
                    ->helperText('Markdown wordt ondersteund, zoals ## tussenkoppen, lijsten en links.')
                    ->rows(12)
                    ->columnSpanFull(),
                TextInput::make('cta_label')->label('CTA label')->maxLength(255),
                TextInput::make('cta_url')->label('CTA URL')->maxLength(255),
                Toggle::make('is_published')->label('Gepubliceerd')->default(false),
                DateTimePicker::make('published_at')->label('Publiceren vanaf'),
                TextInput::make('seo_title')->label('SEO titel')->maxLength(255)->columnSpanFull(),
                Textarea::make('meta_description')->label('Meta description')->rows(3)->maxLength(320)->columnSpanFull(),
                TextInput::make('canonical_url')->label('Canonical URL')->url()->maxLength(255)->columnSpanFull(),
                FileUpload::make('og_image_path')
                    ->label('Open Graph afbeelding')
                    ->image()
                    ->disk('public')
                    ->directory('landing-pages/og-images')
                    ->columnSpanFull(),
                Toggle::make('noindex')->label('Niet indexeren')->default(false),
            ]);
    }
}
