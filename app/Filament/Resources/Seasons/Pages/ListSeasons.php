<?php

namespace App\Filament\Resources\Seasons\Pages;

use App\Filament\Resources\Seasons\SeasonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListSeasons extends ListRecords
{
    use Translatable;

    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
