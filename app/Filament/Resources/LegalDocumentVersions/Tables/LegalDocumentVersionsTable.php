<?php

namespace App\Filament\Resources\LegalDocumentVersions\Tables;

use App\Services\LegalDocumentRepository;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegalDocumentVersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legalDocument.title')->label('Document')->searchable()->sortable(),
                TextColumn::make('version_label')->label('Versie')->searchable()->sortable(),
                IconColumn::make('is_current')
                    ->label('Actueel')
                    ->boolean()
                    ->state(fn ($record): bool => app(LegalDocumentRepository::class)
                        ->currentVersion($record->legalDocument->type)?->is($record) ?? false),
                TextColumn::make('published_at')->label('Gepubliceerd')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('replaced_at')->label('Vervangen')->dateTime('d-m-Y H:i')->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
