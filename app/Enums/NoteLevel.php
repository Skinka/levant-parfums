<?php

namespace App\Enums;

enum NoteLevel: string
{
    case Top = 'top';
    case Heart = 'heart';
    case Base = 'base';

    public function label(): string
    {
        return trans("catalogue.product.fields.notes_{$this->value}");
    }
}
