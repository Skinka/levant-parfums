<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use App\Models\Catalogue\Series;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class SeriesDuoBlock
{
    public static function make(): Block
    {
        return Block::make('series_duo')
            ->label(trans('content.blocks.series_duo.label'))
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('eyebrow'),
                TranslatableTabs::make('title', required: true),
                Repeater::make('items')
                    ->schema([
                        Select::make('series_id')
                            ->label(trans('content.blocks.fields.series_id'))
                            ->options(fn () => Series::query()
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (Series $s) => [$s->id => $s->name])
                                ->all())
                            ->searchable()
                            ->required(),
                        FileUpload::make('image_path')
                            ->label(trans('content.blocks.fields.image_path'))
                            ->disk('public')
                            ->directory('pages/blocks')
                            ->image()
                            ->imageEditor()
                            ->maxSize(4096),
                        TranslatableTabs::make('kicker'),
                        TranslatableTabs::make('title'),
                        TranslatableTabs::make('description', component: Textarea::class),
                        TranslatableTabs::make('cta_label'),
                    ])
                    ->minItems(2)
                    ->maxItems(2)
                    ->defaultItems(2)
                    ->addActionLabel(trans('content.blocks.series_duo.add_item'))
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
