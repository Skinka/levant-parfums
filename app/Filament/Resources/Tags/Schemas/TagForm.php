<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(fn () => trans('catalogue.dictionary.fields.name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        if (! $get('slug')) {
                            $set('slug', Str::slug(is_array($state) ? ($state['uk'] ?? '') : $state));
                        }
                    }),
                TextInput::make('slug')
                    ->label(fn () => trans('catalogue.dictionary.fields.slug'))
                    ->required()
                    ->unique(ignoreRecord: true),
                ColorPicker::make('color')
                    ->label(fn () => trans('catalogue.dictionary.fields.color'))
                    ->required(),
                Toggle::make('is_featured')
                    ->label(fn () => trans('catalogue.dictionary.fields.is_featured')),
                TextInput::make('sort_order')
                    ->label(fn () => trans('catalogue.dictionary.fields.sort_order'))
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label(fn () => trans('catalogue.dictionary.fields.is_active'))
                    ->default(true),
            ]);
    }
}
