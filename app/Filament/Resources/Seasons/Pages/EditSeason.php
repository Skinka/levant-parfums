<?php

namespace App\Filament\Resources\Seasons\Pages;

use App\Filament\Resources\Seasons\SeasonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditSeason extends EditRecord
{
    use Translatable;

    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
