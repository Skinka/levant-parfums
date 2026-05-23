<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageTemplate;
use App\Filament\Resources\Pages\Schemas\Blocks\ArticlesBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\HeroBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\ProductsBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\TextBlock;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('page')
                    ->tabs([
                        Tab::make(trans('content.tabs.main'))
                            ->schema(self::mainTab()),
                        Tab::make(trans('content.tabs.seo'))
                            ->schema(self::seoTab()),
                        Tab::make(trans('content.tabs.images'))
                            ->schema(self::imagesTab()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function mainTab(): array
    {
        $reserved = config('content.reserved_slugs', []);

        return [
            TextInput::make('title')
                ->label(fn () => trans('content.fields.title'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    if (! $get('slug')) {
                        $set('slug', Str::slug(is_array($state) ? ($state['uk'] ?? '') : $state));
                    }
                }),
            TextInput::make('slug')
                ->label(fn () => trans('content.fields.slug'))
                ->required()
                ->rule('not_in:'.implode(',', $reserved))
                ->rule(fn ($record) => Rule::unique('pages', 'slug->uk')->ignore($record?->id))
                ->rule(fn ($record) => Rule::unique('pages', 'slug->en')->ignore($record?->id)),
            Textarea::make('intro')
                ->label(fn () => trans('content.fields.intro'))
                ->rows(3)
                ->maxLength(300),
            Select::make('template')
                ->label(fn () => trans('content.fields.template'))
                ->options(PageTemplate::options())
                ->default(PageTemplate::Simple->value)
                ->required()
                ->live(),
            MarkdownEditor::make('content')
                ->label(fn () => trans('content.fields.content'))
                ->visible(fn (callable $get) => $get('template') === PageTemplate::Simple->value)
                ->required(fn (callable $get) => $get('template') === PageTemplate::Simple->value)
                ->toolbarButtons([
                    'bold', 'italic', 'link', 'heading',
                    'bulletList', 'orderedList', 'blockquote', 'codeBlock',
                ]),
            Builder::make('blocks')
                ->label(fn () => trans('content.fields.blocks'))
                ->visible(fn (callable $get) => $get('template') === PageTemplate::Landing->value)
                ->blocks([
                    HeroBlock::make(),
                    ProductsBlock::make(),
                    TextBlock::make(),
                    ArticlesBlock::make(),
                ])
                ->collapsible()
                ->collapsed()
                ->blockNumbers(false)
                ->addActionLabel(fn () => trans('content.fields.add_block'))
                ->reorderableWithButtons()
                ->cloneable(),
            Toggle::make('is_homepage')
                ->label(fn () => trans('content.fields.is_homepage')),
            Toggle::make('is_published')
                ->label(fn () => trans('content.fields.is_published')),
        ];
    }

    protected static function seoTab(): array
    {
        return [
            TextInput::make('seo_title')
                ->label(fn () => trans('content.fields.seo_title'))
                ->maxLength(70),
            Textarea::make('seo_description')
                ->label(fn () => trans('content.fields.seo_description'))
                ->rows(3)
                ->maxLength(160),
        ];
    }

    protected static function imagesTab(): array
    {
        return [
            SpatieMediaLibraryFileUpload::make('primary')
                ->label(fn () => trans('content.fields.primary'))
                ->collection('primary')
                ->image()
                ->imageEditor()
                ->maxSize(4096),
        ];
    }
}
