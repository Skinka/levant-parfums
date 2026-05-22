<?php

namespace App\Filament\Resources\PerfumeFamilies\Pages;

use App\Filament\Resources\PerfumeFamilies\PerfumeFamilyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPerfumeFamily extends EditRecord
{
    protected static string $resource = PerfumeFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
