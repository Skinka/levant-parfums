<?php

namespace App\Filament\Resources\Tags;

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Filament\Resources\Tags\Schemas\TagForm;
use App\Filament\Resources\Tags\Tables\TagsTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TagResource extends Resource
{
    protected static ?string $model = \App\Models\Catalogue\Tag::class;

    public static function getNavigationGroup(): ?string
    {
        return trans('catalogue.navigation.attributes');
    }

    public static function getNavigationLabel(): string
    {
        return trans('catalogue.navigation.tags');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('catalogue.navigation.tags');
    }

    public static function form(Schema $schema): Schema
    {
        return TagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }
}
