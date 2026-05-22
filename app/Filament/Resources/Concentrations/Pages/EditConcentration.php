<?php

namespace App\Filament\Resources\Concentrations\Pages;

use App\Filament\Resources\Concentrations\ConcentrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConcentration extends EditRecord
{
    protected static string $resource = ConcentrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
