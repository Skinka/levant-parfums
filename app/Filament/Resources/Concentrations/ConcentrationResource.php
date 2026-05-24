<?php

namespace App\Filament\Resources\Concentrations;

use App\Filament\Resources\Concentrations\Pages\CreateConcentration;
use App\Filament\Resources\Concentrations\Pages\EditConcentration;
use App\Filament\Resources\Concentrations\Pages\ListConcentrations;
use App\Filament\Resources\Concentrations\Schemas\ConcentrationForm;
use App\Filament\Resources\Concentrations\Tables\ConcentrationsTable;
use App\Models\Catalogue\Concentration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class ConcentrationResource extends Resource
{
    use Translatable;

    protected static ?string $model = Concentration::class;

    public static function getNavigationGroup(): ?string
    {
        return trans('catalogue.navigation.attributes');
    }

    public static function getNavigationLabel(): string
    {
        return trans('catalogue.navigation.concentrations');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('catalogue.navigation.concentrations');
    }

    public static function form(Schema $schema): Schema
    {
        return ConcentrationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConcentrationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConcentrations::route('/'),
            'create' => CreateConcentration::route('/create'),
            'edit' => EditConcentration::route('/{record}/edit'),
        ];
    }
}
