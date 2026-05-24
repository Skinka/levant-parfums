@props(['product'])

@php
    use App\Enums\NoteLevel;
    $top = $product->notesByLevel(NoteLevel::Top)->get();
    $heart = $product->notesByLevel(NoteLevel::Heart)->get();
    $base = $product->notesByLevel(NoteLevel::Base)->get();
@endphp

<div class="pyramid">
    <div>
        <div class="eyebrow">{{ __('catalogue.public.product.pyramid.title') }}</div>
        <h2 style="margin-top:16px">{{ __('catalogue.public.product.pyramid.subtitle') }}</h2>
    </div>
    <div class="levels">
        @if($top->isNotEmpty())
            <div class="level">
                <div class="lbl">{{ __('catalogue.public.product.pyramid.top') }}</div>
                <div class="notes">
                    @foreach($top as $n)<span class="note">{{ $n->name }}</span>@endforeach
                </div>
            </div>
        @endif
        @if($heart->isNotEmpty())
            <div class="level">
                <div class="lbl">{{ __('catalogue.public.product.pyramid.heart') }}</div>
                <div class="notes">
                    @foreach($heart as $n)<span class="note">{{ $n->name }}</span>@endforeach
                </div>
            </div>
        @endif
        @if($base->isNotEmpty())
            <div class="level">
                <div class="lbl">{{ __('catalogue.public.product.pyramid.base') }}</div>
                <div class="notes">
                    @foreach($base as $n)<span class="note">{{ $n->name }}</span>@endforeach
                </div>
            </div>
        @endif
    </div>
</div>
