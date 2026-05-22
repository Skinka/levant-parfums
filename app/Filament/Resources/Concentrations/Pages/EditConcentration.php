<?php

namespace App\Filament\Resources\Concentrations\Pages;

use App\Filament\Resources\Concentrations\ConcentrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditConcentration extends EditRecord
{
    use Translatable;

    protected static string $resource = ConcentrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
