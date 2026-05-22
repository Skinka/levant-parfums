<?php

namespace App\Filament\Resources\Occasions\Pages;

use App\Filament\Resources\Occasions\OccasionResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateOccasion extends CreateRecord
{
    use Translatable;

    protected static string $resource = OccasionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
