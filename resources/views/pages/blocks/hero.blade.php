@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };
@endphp

<section class="hero reveal" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        @php($floating = $t('floating_label'))
        @if($floating)
            <div class="floating">{{ $floating }}</div>
        @endif

        <div class="grid">
            @php($imagePath = $data['image_path'] ?? null)
            <div class="image-wrap">
                @if($imagePath)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}" alt="" loading="eager">
                @endif
            </div>

            <div class="copy">
                @php($eyebrow = $t('eyebrow'))
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif

                @php($titleTop = $t('title_top'))
                @php($titleBottom = $t('title_bottom'))
                @if($titleTop || $titleBottom)
                    <h1>
                        @if($titleTop){{ $titleTop }}@endif
                        @if($titleBottom)
                            <br><span class="ital">{{ $titleBottom }}</span>
                        @endif
                    </h1>
                @endif

                @php($lead = $t('lead'))
                @if($lead)<p class="lead">{{ $lead }}</p>@endif

                @php($ctaLabel = $t('cta_label'))
                @php($ctaUrl = $data['cta_url'] ?? null)
                @php($secondaryCtaLabel = $t('secondary_cta_label'))
                @php($secondaryCtaUrl = $data['secondary_cta_url'] ?? null)
                @if(($ctaLabel && $ctaUrl) || ($secondaryCtaLabel && $secondaryCtaUrl))
                    <div class="ctas">
                        @if($ctaLabel && $ctaUrl)
                            <a href="{{ $ctaUrl }}" class="btn"><span>{{ $ctaLabel }}</span> <span class="btn-arrow">→</span></a>
                        @endif
                        @if($secondaryCtaLabel && $secondaryCtaUrl)
                            <a href="{{ $secondaryCtaUrl }}" class="btn ghost"><span>{{ $secondaryCtaLabel }}</span></a>
                        @endif
                    </div>
                @endif

                @php($meta = $data['meta'] ?? [])
                @if(! empty($meta))
                    <div class="meta">
                        @foreach($meta as $item)
                            @php($num = $item['num'] ?? '')
                            @php($lbl = ($item['meta_label'][$locale] ?? null) ?: ($item['meta_label']['uk'] ?? ''))
                            <div class="item">
                                <div class="num">{{ $num }}</div>
                                <div class="lbl">{{ $lbl }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
