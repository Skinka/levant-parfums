<?php

namespace App\Filament\Resources\Concentrations\Pages;

use App\Filament\Resources\Concentrations\ConcentrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConcentrations extends ListRecords
{
    protected static string $resource = ConcentrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
