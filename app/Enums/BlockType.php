<?php

namespace App\Enums;

enum BlockType: string
{
    case Hero = 'hero';
    case AboutHero = 'about_hero';
    case Text = 'text';
    case Products = 'products';
    case BrandStory = 'brand_story';
    case SeriesDuo = 'series_duo';
    case Pillars = 'pillars';
    case Testimonials = 'testimonials';
    case Articles = 'articles';

    public function label(): string
    {
        return trans("content.blocks.{$this->value}.label");
    }
}
