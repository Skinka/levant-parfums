<?php

namespace App\Filament\Resources\Occasions;

use App\Filament\Resources\Occasions\Pages\CreateOccasion;
use App\Filament\Resources\Occasions\Pages\EditOccasion;
use App\Filament\Resources\Occasions\Pages\ListOccasions;
use App\Filament\Resources\Occasions\Schemas\OccasionForm;
use App\Filament\Resources\Occasions\Tables\OccasionsTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class OccasionResource extends Resource
{
    protected static ?string $model = \App\Models\Catalogue\Occasion::class;

    public static function getNavigationGroup(): ?string
    {
        return trans('catalogue.navigation.attributes');
    }

    public static function getNavigationLabel(): string
    {
        return trans('catalogue.navigation.occasions');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('catalogue.navigation.occasions');
    }

    public static function form(Schema $schema): Schema
    {
        return OccasionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OccasionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOccasions::route('/'),
            'create' => CreateOccasion::route('/create'),
            'edit' => EditOccasion::route('/{record}/edit'),
        ];
    }
}
