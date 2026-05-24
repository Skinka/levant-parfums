<?php

namespace App\Filament\Resources\PerfumeFamilies;

use App\Filament\Resources\PerfumeFamilies\Pages\CreatePerfumeFamily;
use App\Filament\Resources\PerfumeFamilies\Pages\EditPerfumeFamily;
use App\Filament\Resources\PerfumeFamilies\Pages\ListPerfumeFamilies;
use App\Filament\Resources\PerfumeFamilies\Schemas\PerfumeFamilyForm;
use App\Filament\Resources\PerfumeFamilies\Tables\PerfumeFamiliesTable;
use App\Models\Catalogue\PerfumeFamily;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class PerfumeFamilyResource extends Resource
{
    use Translatable;

    protected static ?string $model = PerfumeFamily::class;

    public static function getNavigationGroup(): ?string
    {
        return trans('catalogue.navigation.attributes');
    }

    public static function getNavigationLabel(): string
    {
        return trans('catalogue.navigation.perfume_families');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('catalogue.navigation.perfume_families');
    }

    public static function form(Schema $schema): Schema
    {
        return PerfumeFamilyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerfumeFamiliesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPerfumeFamilies::route('/'),
            'create' => CreatePerfumeFamily::route('/create'),
            'edit' => EditPerfumeFamily::route('/{record}/edit'),
        ];
    }
}
