<?php

namespace App\Enums;

enum PageTemplate: string
{
    case Simple = 'simple';
    case Landing = 'landing';

    public function label(): string
    {
        return trans("content.template.{$this->value}");
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}
