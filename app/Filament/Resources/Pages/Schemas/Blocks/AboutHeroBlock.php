<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class AboutHeroBlock
{
    public static function make(): Block
    {
        return Block::make('about_hero')
            ->label(trans('content.blocks.about_hero.label'))
            ->icon('heroicon-o-identification')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('eyebrow'),
                TranslatableTabs::make('title', required: true),
                TranslatableTabs::make('lead', component: Textarea::class),
                TranslatableTabs::make('body', component: Textarea::class),

                FileUpload::make('image_path')
                    ->label(trans('content.blocks.fields.image_path'))
                    ->disk('public')
                    ->directory('pages/blocks')
                    ->image()
                    ->imageEditor()
                    ->maxSize(4096),

                Repeater::make('stats')
                    ->label(trans('content.blocks.fields.stats'))
                    ->schema([
                        TextInput::make('num')
                            ->label(trans('content.blocks.fields.meta_num'))
                            ->required()
                            ->maxLength(8),
                        TranslatableTabs::make('meta_label', required: true),
                    ])
                    ->minItems(0)
                    ->maxItems(4)
                    ->defaultItems(4)
                    ->addActionLabel(trans('content.blocks.about_hero.add_stat'))
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
