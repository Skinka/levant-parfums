<?php

namespace App\Filament\Resources\Occasions\Pages;

use App\Filament\Resources\Occasions\OccasionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListOccasions extends ListRecords
{
    use Translatable;

    protected static string $resource = OccasionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
