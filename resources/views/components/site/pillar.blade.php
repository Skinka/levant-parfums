@props([
    'variant' => 'brand_story',
    'eyebrow' => null,
    'label' => null,
    'caption' => null,
    'title' => null,
    'body' => null,
])

@if($variant === 'brand_story')
    <div class="pt">
        <div class="gem" aria-hidden="true"></div>
        @if($label)<div class="l">{{ $label }}</div>@endif
        @if($caption)<div class="b">{{ $caption }}</div>@endif
    </div>
@elseif($variant === 'pillars')
    <div class="step">
        <div class="deco" aria-hidden="true"></div>
        @if($eyebrow)<div class="num">{{ $eyebrow }}</div>@endif
        @if($title)<h3>{{ $title }}</h3>@endif
        @if($body)<p>{!! nl2br(e($body)) !!}</p>@endif
    </div>
@endif
