<?php

namespace App\Filament\Resources\PerfumeFamilies\Pages;

use App\Filament\Resources\PerfumeFamilies\PerfumeFamilyResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreatePerfumeFamily extends CreateRecord
{
    use Translatable;

    protected static string $resource = PerfumeFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
