<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class TestimonialsBlock
{
    public static function make(): Block
    {
        return Block::make('testimonials')
            ->label(trans('content.blocks.testimonials.label'))
            ->icon('heroicon-o-chat-bubble-left-right')
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
                        TranslatableTabs::make('quote', required: true, component: Textarea::class),
                        TextInput::make('author')
                            ->label(trans('content.blocks.fields.author'))
                            ->required()
                            ->maxLength(120),
                        TranslatableTabs::make('city'),
                        TextInput::make('rating')
                            ->label(trans('content.blocks.fields.rating'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                    ])
                    ->minItems(2)
                    ->defaultItems(2)
                    ->addActionLabel(trans('content.blocks.testimonials.add_item'))
                    ->reorderable(),
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
