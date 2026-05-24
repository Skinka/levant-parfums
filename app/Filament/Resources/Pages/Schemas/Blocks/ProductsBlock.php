<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use App\Models\Catalogue\Product;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ProductsBlock
{
    public static function make(): Block
    {
        return Block::make('products')
            ->label(trans('content.blocks.products.label'))
            ->icon('heroicon-o-shopping-bag')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('eyebrow'),
                TranslatableTabs::make('title', required: true),
                TranslatableTabs::make('cta_label'),
                TextInput::make('cta_url')
                    ->label(trans('content.blocks.fields.cta_url'))
                    ->maxLength(2048)
                    ->helperText(trans('content.blocks.fields.cta_url_helper')),
                Repeater::make('items')
                    ->schema([
                        Select::make('product_id')
                            ->label(trans('content.blocks.fields.product_id'))
                            ->options(fn () => Product::query()
                                ->orderBy('slug')
                                ->get()
                                ->mapWithKeys(fn (Product $p) => [$p->id => $p->name])
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->reorderable()
                    ->defaultItems(0)
                    ->addActionLabel(trans('content.blocks.products.add_item')),
            ]);
    }

    protected static function commonFields(): array
    {
        return [
            Toggle::make('is_visible')
                ->label(trans('content.blocks.fields.is_visible'))
                ->default(true),
            TextInput::make('anchor')
                ->label(trans('content.blocks.fields.anchor'))
                ->prefix('#')
                ->alphaDash(),
        ];
    }
}
