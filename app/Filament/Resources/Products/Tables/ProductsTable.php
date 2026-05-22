<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('primary')
                    ->collection('primary')
                    ->conversion('thumb')
                    ->circular(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku')->searchable(),
                TextColumn::make('series.name')
                    ->label(fn () => trans('catalogue.product.fields.series')),
                TextColumn::make('perfumeFamily.name')
                    ->label(fn () => trans('catalogue.product.fields.perfume_family')),
                TextColumn::make('concentration.abbreviation')
                    ->label(fn () => trans('catalogue.product.fields.concentration')),
                TextColumn::make('price_uah')->suffix(' ₴')->sortable(),
                TextColumn::make('price_eur')->suffix(' €')->sortable(),
                IconColumn::make('in_stock')->boolean(),
                IconColumn::make('is_published')->boolean(),
            ])
            ->filters([
                SelectFilter::make('series_id')
                    ->relationship('series', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                SelectFilter::make('perfume_family_id')
                    ->relationship('perfumeFamily', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                SelectFilter::make('concentration_id')
                    ->relationship('concentration', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                SelectFilter::make('gender')->options([
                    'male' => trans('catalogue.gender.male'),
                    'female' => trans('catalogue.gender.female'),
                    'unisex' => trans('catalogue.gender.unisex'),
                ]),
                TernaryFilter::make('is_published'),
                TernaryFilter::make('in_stock'),
                SelectFilter::make('tags')
                    ->relationship('tags', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->multiple()
                    ->preload(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->action(fn ($records) => $records->each->update(['is_published' => true, 'published_at' => now()])),
                    BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->action(fn ($records) => $records->each->update(['is_published' => false])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
