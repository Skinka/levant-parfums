<?php

namespace App\Filament\Resources\Articles\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('primary')
                    ->collection('primary')
                    ->conversion('thumb'),
                TextColumn::make('title')
                    ->label(fn () => trans('content.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->label(fn () => trans('content.fields.slug'))
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
                    ->label(fn () => trans('content.fields.is_published'))
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label(fn () => trans('content.fields.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record?->published_at?->isFuture() ? 'warning' : null),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                TernaryFilter::make('is_published'),
                Filter::make('scheduled')
                    ->label(fn () => trans('content.filters.scheduled'))
                    ->query(fn ($query) => $query->where('published_at', '>', now())),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label(trans('content.actions.publish'))
                        ->action(fn ($records) => $records->each->update(['is_published' => true, 'published_at' => now()])),
                    BulkAction::make('unpublish')
                        ->label(trans('content.actions.unpublish'))
                        ->action(fn ($records) => $records->each->update(['is_published' => false])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
