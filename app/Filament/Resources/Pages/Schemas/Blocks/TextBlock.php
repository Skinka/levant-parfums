<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class TextBlock
{
    public static function make(): Block
    {
        return Block::make('text')
            ->label(trans('content.blocks.text.label'))
            ->icon('heroicon-o-document-text')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('title'),
                TranslatableTabs::make('body', required: true, component: MarkdownEditor::class),
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
