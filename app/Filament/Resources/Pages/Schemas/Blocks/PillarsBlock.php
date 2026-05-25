<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class PillarsBlock
{
    public static function make(): Block
    {
        return Block::make('pillars')
            ->label(trans('content.blocks.pillars.label'))
            ->icon('heroicon-o-list-bullet')
            ->schema([
                ...self::commonFields(),
                Select::make('surface')
                    ->label(trans('content.blocks.fields.surface'))
                    ->options([
                        'default' => trans('content.blocks.surface.default'),
                        'tinted' => trans('content.blocks.surface.tinted'),
                    ])
                    ->default('default')
                    ->required(),
                TranslatableTabs::make('eyebrow'),
                TranslatableTabs::make('title', required: true),
                TranslatableTabs::make('body', component: Textarea::class),
                Repeater::make('items')
                    ->schema([
                        TranslatableTabs::make('eyebrow'),
                        TranslatableTabs::make('title', required: true),
                        TranslatableTabs::make('body', component: Textarea::class),
                    ])
                    ->minItems(3)
                    ->maxItems(4)
                    ->defaultItems(3)
                    ->addActionLabel(trans('content.blocks.pillars.add_item'))
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
