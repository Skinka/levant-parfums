<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ContactBlock
{
    public static function make(): Block
    {
        return Block::make('contact')
            ->label(trans('content.blocks.contact.label'))
            ->icon('heroicon-o-envelope')
            ->schema([
                ...self::commonFields(),

                TranslatableTabs::make('eyebrow'),
                TranslatableTabs::make('title', required: true),
                TranslatableTabs::make('lead', component: Textarea::class),

                TranslatableTabs::make('address', component: Textarea::class),
                TextInput::make('phone')
                    ->label(trans('content.blocks.fields.phone')),
                TextInput::make('phone_href')
                    ->label(trans('content.blocks.fields.phone_href'))
                    ->helperText(trans('content.blocks.fields.phone_href_hint'))
                    ->regex('/^\+?[0-9]+$/'),
                TextInput::make('email')
                    ->label(trans('content.blocks.fields.email'))
                    ->email(),
                TranslatableTabs::make('hours'),

                TranslatableTabs::make('form_eyebrow'),
                TranslatableTabs::make('form_title', required: true),

                Repeater::make('socials')
                    ->label(trans('content.blocks.fields.socials'))
                    ->schema([
                        TextInput::make('label')
                            ->label(trans('content.blocks.fields.social_label'))
                            ->required(),
                        TextInput::make('url')
                            ->label(trans('content.blocks.fields.social_url'))
                            ->url()
                            ->required(),
                    ])
                    ->maxItems(6)
                    ->reorderable()
                    ->addActionLabel(trans('content.blocks.contact.add_social')),
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
