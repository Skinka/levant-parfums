<?php

namespace App\Filament\Resources\PerfumeFamilies\Pages;

use App\Filament\Resources\PerfumeFamilies\PerfumeFamilyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPerfumeFamilies extends ListRecords
{
    protected static string $resource = PerfumeFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
