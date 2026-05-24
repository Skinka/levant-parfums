<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use App\Models\Content\Article;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ArticlesBlock
{
    public static function make(): Block
    {
        return Block::make('articles')
            ->label(trans('content.blocks.articles.label'))
            ->icon('heroicon-o-newspaper')
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
                        Select::make('article_id')
                            ->label(trans('content.blocks.fields.article_id'))
                            ->options(fn () => Article::query()
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (Article $a) => [$a->id => $a->title])
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->minItems(3)
                    ->maxItems(3)
                    ->defaultItems(3)
                    ->reorderable()
                    ->addActionLabel(trans('content.blocks.articles.add_item')),
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
