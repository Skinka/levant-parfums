@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };
    $eyebrow = $t('eyebrow');
    $title = $t('title');
    $body = $t('body');
    $pillars = $data['pillars'] ?? [];
@endphp

<section class="threepoints reveal" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        <div class="head">
            @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
            @if($title)<h2>{{ $title }}</h2>@endif
            @if($body)<p>{{ $body }}</p>@endif
        </div>

        @if(! empty($pillars))
            <div class="points">
                @foreach($pillars as $i => $pillar)
                    @php
                        $label = ($pillar['pillar_label'][$locale] ?? null) ?: ($pillar['pillar_label']['uk'] ?? '');
                        $caption = ($pillar['pillar_caption'][$locale] ?? null) ?: ($pillar['pillar_caption']['uk'] ?? '');
                    @endphp
                    <x-site.pillar variant="brand_story" :label="$label" :caption="$caption" />
                    @if($i < count($pillars) - 1)
                        <div class="conn" aria-hidden="true"></div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
