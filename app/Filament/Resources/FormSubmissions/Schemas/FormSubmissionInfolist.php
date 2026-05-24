<?php

namespace App\Filament\Resources\FormSubmissions\Schemas;

use App\Forms\Models\FormSubmission;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FormSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                TextEntry::make('type')
                    ->label(trans('forms.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => trans("forms.types.{$state}")),

                TextEntry::make('status')
                    ->label(trans('forms.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => trans("forms.statuses.{$state}")),

                TextEntry::make('created_at')
                    ->label(trans('forms.fields.created_at'))
                    ->dateTime(),

                TextEntry::make('subject')
                    ->label(trans('forms.fields.subject'))
                    ->state(function (FormSubmission $record): ?string {
                        $s = $record->subject;
                        if (! $s) {
                            return null;
                        }

                        return class_basename($s).'#'.$s->getKey();
                    })
                    ->placeholder('—'),
            ])->columns(2),

            Section::make(trans('forms.fields.data'))->schema([
                TextEntry::make('data')
                    ->state(fn (FormSubmission $record): string => self::formatKeyValue($record->data ?? [])),
            ]),

            Section::make(trans('forms.fields.meta'))->schema([
                TextEntry::make('meta')
                    ->state(fn (FormSubmission $record): string => self::formatKeyValue($record->meta ?? [])),
                TextEntry::make('locale')->label(trans('forms.fields.locale')),
                TextEntry::make('handled_at')->label(trans('forms.fields.handled_at'))->dateTime()->placeholder('—'),
            ])->columns(2)->collapsed(),
        ]);
    }

    private static function formatKeyValue(array $data): string
    {
        if ($data === []) {
            return '—';
        }
        $lines = [];
        foreach ($data as $k => $v) {
            $value = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            $lines[] = "**{$k}:** {$value}";
        }

        return implode("\n\n", $lines);
    }
}
