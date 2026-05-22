<?php

namespace App\Filament\Resources\Audiences;

use App\Filament\Resources\Audiences\Pages\CreateAudience;
use App\Filament\Resources\Audiences\Pages\EditAudience;
use App\Filament\Resources\Audiences\Pages\ListAudiences;
use App\Filament\Resources\Audiences\Schemas\AudienceForm;
use App\Filament\Resources\Audiences\Tables\AudiencesTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AudienceResource extends Resource
{
    protected static ?string $model = \App\Models\Catalogue\Audience::class;

    public static function getNavigationGroup(): ?string
    {
        return trans('catalogue.navigation.attributes');
    }

    public static function getNavigationLabel(): string
    {
        return trans('catalogue.navigation.audiences');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('catalogue.navigation.audiences');
    }

    public static function form(Schema $schema): Schema
    {
        return AudienceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AudiencesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAudiences::route('/'),
            'create' => CreateAudience::route('/create'),
            'edit' => EditAudience::route('/{record}/edit'),
        ];
    }
}
