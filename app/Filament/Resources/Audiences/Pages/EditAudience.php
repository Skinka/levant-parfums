<?php

namespace App\Filament\Resources\Audiences\Pages;

use App\Filament\Resources\Audiences\AudienceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditAudience extends EditRecord
{
    use Translatable;

    protected static string $resource = AudienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
