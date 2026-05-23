<?php

namespace App\Enums;

enum BlockType: string
{
    case Hero = 'hero';
    case Products = 'products';
    case Text = 'text';
    case Articles = 'articles';

    public function label(): string
    {
        return trans("content.blocks.{$this->value}.label");
    }
}
