<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\NoteLevel;
use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateProduct extends CreateRecord
{
    use Translatable;

    protected static string $resource = ProductResource::class;

    protected array $cachedNotes = [];

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (NoteLevel::cases() as $level) {
            $key = "notes_{$level->value}";
            $this->cachedNotes[$level->value] = $data[$key] ?? [];
            unset($data[$key]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach (NoteLevel::cases() as $level) {
            foreach (($this->cachedNotes[$level->value] ?? []) as $i => $row) {
                if (! empty($row['note_id'])) {
                    $this->record->notes()->attach($row['note_id'], [
                        'level' => $level->value,
                        'sort_order' => $i,
                    ]);
                }
            }
        }
    }
}
