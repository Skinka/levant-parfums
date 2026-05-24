<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\Gender;
use App\Enums\NoteLevel;
use App\Models\Catalogue\Note;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('product')
                    ->tabs([
                        Tab::make(trans('catalogue.product.tabs.main'))
                            ->schema(self::mainTab()),
                        Tab::make(trans('catalogue.product.tabs.description'))
                            ->schema(self::descriptionTab()),
                        Tab::make(trans('catalogue.product.tabs.aroma'))
                            ->schema(self::aromaTab()),
                        Tab::make(trans('catalogue.product.tabs.character'))
                            ->schema(self::characterTab()),
                        Tab::make(trans('catalogue.product.tabs.inspired_by'))
                            ->schema(self::inspiredByTab()),
                        Tab::make(trans('catalogue.product.tabs.marking'))
                            ->schema(self::markingTab()),
                        Tab::make(trans('catalogue.product.tabs.seo'))
                            ->schema(self::seoTab()),
                        Tab::make(trans('catalogue.product.tabs.images'))
                            ->schema(self::imagesTab()),
                    ])
                    ->columnSpan(['lg' => 2]),

                Section::make('sidebar')
                    ->schema(self::sidebar())
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    protected static function mainTab(): array
    {
        return [
            TextInput::make('name')
                ->label(fn () => trans('catalogue.product.fields.name'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    if (! $get('slug')) {
                        $set('slug', Str::slug(is_array($state) ? ($state['uk'] ?? '') : $state));
                    }
                }),
            TextInput::make('slug')
                ->label(fn () => trans('catalogue.product.fields.slug'))
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('sku')
                ->label(fn () => trans('catalogue.product.fields.sku'))
                ->required()
                ->unique(ignoreRecord: true),
            Radio::make('gender')
                ->label(fn () => trans('catalogue.product.fields.gender'))
                ->options(Gender::options())
                ->required()
                ->inline(),
            TextInput::make('volume_ml')
                ->label(fn () => trans('catalogue.product.fields.volume_ml'))
                ->numeric()
                ->default(config('catalogue.default_volume_ml'))
                ->required(),
            Toggle::make('is_published')
                ->label(fn () => trans('catalogue.product.fields.is_published'))
                ->live(),
            DateTimePicker::make('published_at')
                ->label(fn () => trans('catalogue.product.fields.published_at'))
                ->visible(fn (callable $get) => $get('is_published')),
            Toggle::make('in_stock')
                ->label(fn () => trans('catalogue.product.fields.in_stock'))
                ->default(true),
        ];
    }

    protected static function descriptionTab(): array
    {
        return [
            Textarea::make('tagline')
                ->label(fn () => trans('catalogue.product.fields.tagline'))
                ->rows(2),
            Textarea::make('description')
                ->label(fn () => trans('catalogue.product.fields.description'))
                ->rows(6),
        ];
    }

    protected static function aromaTab(): array
    {
        return [
            Select::make('perfume_family_id')
                ->label(fn () => trans('catalogue.product.fields.perfume_family'))
                ->relationship('perfumeFamily', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                ->searchable()
                ->preload(),
            Select::make('concentration_id')
                ->label(fn () => trans('catalogue.product.fields.concentration'))
                ->relationship('concentration', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                ->searchable()
                ->preload(),
            Select::make('series_id')
                ->label(fn () => trans('catalogue.product.fields.series'))
                ->relationship('series', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                ->searchable()
                ->preload(),

            self::notesRepeater('notes_top', NoteLevel::Top),
            self::notesRepeater('notes_heart', NoteLevel::Heart),
            self::notesRepeater('notes_base', NoteLevel::Base),
        ];
    }

    protected static function characterTab(): array
    {
        return [
            TextInput::make('character')
                ->label(fn () => trans('catalogue.product.fields.character'))
                ->maxLength(160),
            Textarea::make('why')
                ->label(fn () => trans('catalogue.product.fields.why'))
                ->rows(4),
            Select::make('sillage_score')
                ->label(fn () => trans('catalogue.product.fields.sillage_score'))
                ->options([
                    1 => trans('catalogue.product.sillage_words.1'),
                    2 => trans('catalogue.product.sillage_words.2'),
                    3 => trans('catalogue.product.sillage_words.3'),
                    4 => trans('catalogue.product.sillage_words.4'),
                    5 => trans('catalogue.product.sillage_words.5'),
                ])
                ->native(false),
            Select::make('longevity_hours')
                ->label(fn () => trans('catalogue.product.fields.longevity_hours'))
                ->options(collect([2, 4, 6, 8, 10, 12])->mapWithKeys(fn ($h) => [$h => $h.' h'])->all())
                ->native(false),
        ];
    }

    protected static function notesRepeater(string $key, NoteLevel $level): Repeater
    {
        return Repeater::make($key)
            ->label(fn () => trans("catalogue.product.fields.{$key}"))
            ->schema([
                Select::make('note_id')
                    ->options(fn () => Note::query()->orderBy('slug')->get()->mapWithKeys(fn (Note $n) => [$n->id => $n->name])->all())
                    ->searchable()
                    ->required(),
            ])
            ->orderColumn('sort_order')
            ->reorderable()
            ->defaultItems(0)
            ->addActionLabel(trans('catalogue.product.fields.'.$key));
    }

    protected static function inspiredByTab(): array
    {
        return [
            Select::make('inspired_brand_id')
                ->label(fn () => trans('catalogue.product.fields.inspired_brand'))
                ->relationship('inspiredBrand', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                ->searchable()
                ->preload(),
            TextInput::make('inspired_perfume_name')
                ->label(fn () => trans('catalogue.product.fields.inspired_perfume_name'))
                ->maxLength(255),
        ];
    }

    protected static function markingTab(): array
    {
        return [
            Select::make('tags')
                ->label(fn () => trans('catalogue.product.fields.tags'))
                ->relationship('tags', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                ->multiple()->searchable()->preload(),
            Select::make('seasons')
                ->label(fn () => trans('catalogue.product.fields.seasons'))
                ->relationship('seasons', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                ->multiple()->searchable()->preload(),
            Select::make('occasions')
                ->label(fn () => trans('catalogue.product.fields.occasions'))
                ->relationship('occasions', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                ->multiple()->searchable()->preload(),
            Select::make('audiences')
                ->label(fn () => trans('catalogue.product.fields.audiences'))
                ->relationship('audiences', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                ->multiple()->searchable()->preload(),
        ];
    }

    protected static function seoTab(): array
    {
        return [
            TextInput::make('seo_title')
                ->label(fn () => trans('catalogue.product.fields.seo_title'))
                ->maxLength(255),
            Textarea::make('seo_description')
                ->label(fn () => trans('catalogue.product.fields.seo_description'))
                ->rows(3)
                ->maxLength(500),
        ];
    }

    protected static function imagesTab(): array
    {
        return [
            SpatieMediaLibraryFileUpload::make('primary')
                ->label(fn () => trans('catalogue.product.fields.primary_image'))
                ->collection('primary')
                ->image()
                ->imageEditor()
                ->maxSize(5120),

            SpatieMediaLibraryFileUpload::make('gallery')
                ->label(fn () => trans('catalogue.product.fields.gallery'))
                ->collection('gallery')
                ->multiple()
                ->image()
                ->reorderable()
                ->appendFiles()
                ->maxSize(5120),
        ];
    }

    protected static function sidebar(): array
    {
        return [
            TextInput::make('price_uah')
                ->label(fn () => trans('catalogue.product.fields.price_uah'))
                ->numeric()
                ->prefix('₴')
                ->required(),
            TextInput::make('price_eur')
                ->label(fn () => trans('catalogue.product.fields.price_eur'))
                ->numeric()
                ->prefix('€')
                ->required(),
        ];
    }
}
