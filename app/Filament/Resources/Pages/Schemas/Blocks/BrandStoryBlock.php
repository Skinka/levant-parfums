<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class BrandStoryBlock
{
    public static function make(): Block
    {
        return Block::make('brand_story')
            ->label(trans('content.blocks.brand_story.label'))
            ->icon('heroicon-o-map')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('eyebrow'),
                TranslatableTabs::make('title', required: true),
                TranslatableTabs::make('body', component: Textarea::class),
                Repeater::make('pillars')
                    ->label(trans('content.blocks.fields.pillars'))
                    ->schema([
                        TranslatableTabs::make('pillar_label', required: true),
                        TranslatableTabs::make('pillar_caption'),
                    ])
                    ->minItems(3)
                    ->maxItems(3)
                    ->defaultItems(3)
                    ->addActionLabel(trans('content.blocks.brand_story.add_pillar'))
                    ->reorderable(false),
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
