<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\NoteLevel;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditProduct extends EditRecord
{
    use Translatable;

    protected static string $resource = ProductResource::class;

    protected array $cachedNotes = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (NoteLevel::cases() as $level) {
            $key = "notes_{$level->value}";
            $data[$key] = $this->record->notes()
                ->wherePivot('level', $level->value)
                ->orderBy('product_note.sort_order')
                ->get()
                ->map(fn ($note) => ['note_id' => $note->id])
                ->all();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (NoteLevel::cases() as $level) {
            $key = "notes_{$level->value}";
            $this->cachedNotes[$level->value] = $data[$key] ?? [];
            unset($data[$key]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $sync = [];
        foreach (NoteLevel::cases() as $level) {
            $rows = $this->cachedNotes[$level->value] ?? [];
            foreach ($rows as $i => $row) {
                if (! empty($row['note_id'])) {
                    $sync[] = [
                        'note_id' => $row['note_id'],
                        'level' => $level->value,
                        'sort_order' => $i,
                    ];
                }
            }
        }

        $this->record->notes()->detach();
        foreach ($sync as $row) {
            $this->record->notes()->attach($row['note_id'], [
                'level' => $row['level'],
                'sort_order' => $row['sort_order'],
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
