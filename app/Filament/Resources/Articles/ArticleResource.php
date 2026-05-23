<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Articles\Schemas\ArticleForm;
use App\Filament\Resources\Articles\Tables\ArticlesTable;
use App\Models\Content\Article;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class ArticleResource extends Resource
{
    use Translatable;

    protected static ?string $model = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    public static function getNavigationGroup(): ?string
    {
        return trans('content.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return trans('content.navigation.articles');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('content.article.plural');
    }

    public static function getModelLabel(): string
    {
        return trans('content.article.singular');
    }

    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
