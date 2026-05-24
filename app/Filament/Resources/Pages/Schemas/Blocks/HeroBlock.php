<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class HeroBlock
{
    public static function make(): Block
    {
        return Block::make('hero')
            ->label(trans('content.blocks.hero.label'))
            ->icon('heroicon-o-rectangle-stack')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('eyebrow'),
                TranslatableTabs::make('title_top', required: true),
                TranslatableTabs::make('title_bottom'),
                TranslatableTabs::make('lead', component: Textarea::class),
                TranslatableTabs::make('floating_label'),

                TranslatableTabs::make('cta_label'),
                TextInput::make('cta_url')
                    ->label(trans('content.blocks.fields.cta_url'))
                    ->maxLength(2048)
                    ->helperText(trans('content.blocks.fields.cta_url_helper')),
                TranslatableTabs::make('secondary_cta_label'),
                TextInput::make('secondary_cta_url')
                    ->label(trans('content.blocks.fields.secondary_cta_url'))
                    ->maxLength(2048),

                FileUpload::make('image_path')
                    ->label(trans('content.blocks.fields.image_path'))
                    ->disk('public')
                    ->directory('pages/blocks')
                    ->image()
                    ->imageEditor()
                    ->maxSize(4096),

                Repeater::make('meta')
                    ->label(trans('content.blocks.fields.meta'))
                    ->schema([
                        TextInput::make('num')
                            ->label(trans('content.blocks.fields.meta_num'))
                            ->required()
                            ->maxLength(8),
                        TranslatableTabs::make('meta_label', required: true),
                    ])
                    ->minItems(3)
                    ->maxItems(3)
                    ->defaultItems(3)
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
