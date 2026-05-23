<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateArticle extends CreateRecord
{
    use Translatable;

    protected static string $resource = ArticleResource::class;

    protected array $cachedProducts = [];

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make()];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->cachedProducts = $data['products'] ?? [];
        unset($data['products']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->cachedProducts as $i => $row) {
            if (! empty($row['product_id'])) {
                $this->record->products()->attach($row['product_id'], ['sort_order' => $i]);
            }
        }
    }
}
