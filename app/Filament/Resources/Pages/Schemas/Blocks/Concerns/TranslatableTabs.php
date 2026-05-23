<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class TranslatableTabs
{
    /**
     * Build a tabbed text field that writes to {field}.{locale} keys inside
     * the block's `data` payload. Use TextInput by default; pass Textarea
     * (or MarkdownEditor) via $component for longer fields.
     *
     * @param  class-string  $component  Form component class with a static ::make($name) factory.
     */
    public static function make(string $field, bool $required = false, string $component = TextInput::class): Tabs
    {
        $locales = config('catalogue.locales', ['uk', 'en']);

        return Tabs::make($field)
            ->label(trans("content.blocks.fields.{$field}"))
            ->tabs(collect($locales)
                ->map(fn (string $locale) => Tab::make(strtoupper($locale))
                    ->schema([
                        $component::make("{$field}.{$locale}")
                            ->label(false)
                            ->required($required && $locale === 'uk'),
                    ]))
                ->all());
    }
}
