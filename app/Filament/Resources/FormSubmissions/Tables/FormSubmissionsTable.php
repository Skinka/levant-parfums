<?php

namespace App\Filament\Resources\FormSubmissions\Tables;

use App\Forms\Models\FormSubmission;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class FormSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label(trans('forms.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => trans("forms.types.{$state}"))
                    ->sortable(),

                TextColumn::make('preorder')
                    ->label('')
                    ->badge()
                    ->color('warning')
                    ->getStateUsing(fn ($record) => ($record->data['is_preorder'] ?? false) ? 'PRE-ORDER' : null),

                TextColumn::make('status')
                    ->label(trans('forms.fields.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        FormSubmission::STATUS_NEW => 'warning',
                        FormSubmission::STATUS_READ => 'info',
                        FormSubmission::STATUS_PROCESSED => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => trans("forms.statuses.{$state}"))
                    ->sortable(),

                TextColumn::make('summary')
                    ->label(trans('forms.fields.summary'))
                    ->state(function (FormSubmission $record): string {
                        $name = $record->data['name'] ?? null;
                        $email = $record->data['email'] ?? null;
                        if ($name && $email) {
                            return "{$name} <{$email}>";
                        }

                        return $name
                            ?? $email
                            ?? Str::limit((string) ($record->data['message'] ?? ''), 60);
                    })
                    ->wrap(),

                TextColumn::make('subject')
                    ->label(trans('forms.fields.subject'))
                    ->state(function (FormSubmission $record): ?string {
                        $s = $record->subject;
                        if (! $s) {
                            return null;
                        }
                        $label = is_array($s->name ?? null) ? ($s->name[app()->getLocale()] ?? null) : ($s->name ?? null);

                        return $label ?? class_basename($s).'#'.$s->getKey();
                    })
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(trans('forms.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(trans('forms.fields.type'))
                    ->options(collect(config('forms.types'))
                        ->map(fn (string $cls) => app($cls))
                        ->mapWithKeys(fn ($t) => [$t->key() => $t->label()])
                        ->all()),

                SelectFilter::make('status')
                    ->label(trans('forms.fields.status'))
                    ->options(collect(FormSubmission::STATUSES)
                        ->mapWithKeys(fn (string $s) => [$s => trans("forms.statuses.{$s}")])
                        ->all()),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('mark_read')
                    ->label(trans('forms.actions.mark_read'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (FormSubmission $r) => $r->status === FormSubmission::STATUS_NEW)
                    ->action(fn (FormSubmission $r) => $r->markRead()),
                Action::make('mark_processed')
                    ->label(trans('forms.actions.mark_processed'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (FormSubmission $r) => $r->status !== FormSubmission::STATUS_PROCESSED)
                    ->action(fn (FormSubmission $r) => $r->markProcessed()),
                Action::make('mark_new')
                    ->label(trans('forms.actions.mark_new'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (FormSubmission $r) => $r->status !== FormSubmission::STATUS_NEW)
                    ->action(fn (FormSubmission $r) => $r->markNew()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_mark_read')
                        ->label(trans('forms.actions.mark_read'))
                        ->action(fn (Collection $records) => $records->each->markRead()),
                    BulkAction::make('bulk_mark_processed')
                        ->label(trans('forms.actions.mark_processed'))
                        ->action(fn (Collection $records) => $records->each->markProcessed()),
                ]),
            ]);
    }
}
