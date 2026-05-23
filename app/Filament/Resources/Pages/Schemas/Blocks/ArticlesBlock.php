<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

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
                    ->reorderable()
                    ->defaultItems(0)
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
