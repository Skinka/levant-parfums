<?php

namespace App\Filament\Resources\PerfumeFamilies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PerfumeFamilyForm
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
