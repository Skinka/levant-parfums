<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Models\Catalogue\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
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

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('article')
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
                ->rule(fn ($record) => Rule::unique('articles', 'slug->uk')->ignore($record?->id))
                ->rule(fn ($record) => Rule::unique('articles', 'slug->en')->ignore($record?->id)),
            Textarea::make('intro')
                ->label(fn () => trans('content.fields.intro'))
                ->rows(3)
                ->maxLength(300),
            TextInput::make('category')
                ->label(fn () => trans('content.fields.category')),
            TextInput::make('read_time_minutes')
                ->label(fn () => trans('content.fields.read_time_minutes'))
                ->numeric()
                ->minValue(1)
                ->maxValue(120)
                ->suffix(fn () => trans('content.units.minutes')),
            MarkdownEditor::make('content')
                ->label(fn () => trans('content.fields.content'))
                ->required()
                ->toolbarButtons([
                    'bold', 'italic', 'link', 'heading',
                    'bulletList', 'orderedList', 'blockquote', 'codeBlock',
                ]),
            Toggle::make('is_published')
                ->label(fn () => trans('content.fields.is_published'))
                ->live(),
            DateTimePicker::make('published_at')
                ->label(fn () => trans('content.fields.published_at'))
                ->seconds(false)
                ->helperText(trans('content.hints.published_at'))
                ->visible(fn (callable $get) => $get('is_published')),
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
            self::productsRepeater(),
        ];
    }

    protected static function productsRepeater(): Repeater
    {
        return Repeater::make('products')
            ->label(fn () => trans('content.fields.products'))
            ->schema([
                Select::make('product_id')
                    ->label(fn () => trans('content.fields.product_id'))
                    ->options(fn () => Product::query()
                        ->orderBy('slug')
                        ->get()
                        ->mapWithKeys(fn (Product $p) => [$p->id => $p->name])
                        ->all())
                    ->searchable()
                    ->required(),
            ])
            ->orderColumn('sort_order')
            ->reorderable()
            ->defaultItems(0)
            ->addActionLabel(fn () => trans('content.fields.add_product'));
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
