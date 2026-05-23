<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditArticle extends EditRecord
{
    use Translatable;

    protected static string $resource = ArticleResource::class;

    protected array $cachedProducts = [];

    private bool $productsCached = false;

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make(), DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['products'] = $this->record->products()
            ->orderBy('article_product.sort_order')
            ->get()
            ->map(fn ($p) => ['product_id' => $p->id])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! $this->productsCached) {
            $this->cachedProducts = $data['products'] ?? [];
            $this->productsCached = true;
        }
        unset($data['products']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->products()->detach();
        foreach ($this->cachedProducts as $i => $row) {
            if (! empty($row['product_id'])) {
                $this->record->products()->attach($row['product_id'], ['sort_order' => $i]);
            }
        }

        $this->productsCached = false;
    }
}
