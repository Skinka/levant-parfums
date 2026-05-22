<?php

namespace App\Filament\Resources\PerfumeFamilies\Pages;

use App\Filament\Resources\PerfumeFamilies\PerfumeFamilyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListPerfumeFamilies extends ListRecords
{
    use Translatable;

    protected static string $resource = PerfumeFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
