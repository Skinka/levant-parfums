<?php

namespace App\Filament\Resources\Concentrations\Pages;

use App\Filament\Resources\Concentrations\ConcentrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListConcentrations extends ListRecords
{
    use Translatable;

    protected static string $resource = ConcentrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
