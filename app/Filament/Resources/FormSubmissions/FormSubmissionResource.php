<?php

namespace App\Filament\Resources\FormSubmissions;

use App\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Filament\Resources\FormSubmissions\Pages\ViewFormSubmission;
use App\Filament\Resources\FormSubmissions\Schemas\FormSubmissionInfolist;
use App\Filament\Resources\FormSubmissions\Tables\FormSubmissionsTable;
use App\Forms\Models\FormSubmission;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return trans('forms.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return trans('forms.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('forms.resource.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FormSubmission::query()->where('status', FormSubmission::STATUS_NEW)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return FormSubmissionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FormSubmissionInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
            'view' => ViewFormSubmission::route('/{record}'),
        ];
    }
}
