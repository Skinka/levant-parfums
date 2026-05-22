<?php

namespace App\Filament\Resources\Notes\Pages;

use App\Filament\Resources\Notes\NoteResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateNote extends CreateRecord
{
    use Translatable;

    protected static string $resource = NoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
