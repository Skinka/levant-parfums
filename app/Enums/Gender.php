<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Unisex = 'unisex';

    public function label(): string
    {
        return trans("catalogue.gender.{$this->value}");
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $g) => [$g->value => $g->label()])
            ->all();
    }
}
