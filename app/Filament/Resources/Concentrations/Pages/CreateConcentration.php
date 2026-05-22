<?php

namespace App\Filament\Resources\Concentrations\Pages;

use App\Filament\Resources\Concentrations\ConcentrationResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateConcentration extends CreateRecord
{
    use Translatable;

    protected static string $resource = ConcentrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
