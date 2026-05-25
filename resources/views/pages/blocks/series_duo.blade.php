@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };
    $eyebrow = $t('eyebrow');
    $title = $t('title');
    $items = $data['items'] ?? [];
@endphp

<section class="collections reveal" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        <div class="section-head">
            <div>
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if($title)<h2 style="margin-top: 12px;">{{ $title }}</h2>@endif
            </div>
        </div>

        @if(! empty($items))
            <div class="grid">
                @foreach($items as $item)
                    @php
                        $tr = function (string $key) use ($item, $locale) {
                            $value = $item[$key][$locale] ?? null;
                            return filled($value) ? $value : ($item[$key]['uk'] ?? '');
                        };
                        $kicker = $tr('kicker');
                        $itemTitle = $tr('title');
                        $description = $tr('description');
                        $ctaLabel = $tr('cta_label');
                        $imagePath = $item['image_path'] ?? null;

                        $series = ! empty($item['series_id'])
                            ? \App\Models\Catalogue\Series::find($item['series_id'])
                            : null;
                        $ctaUrl = $series
                            ? route('products.index', ['series' => $series->slug])
                            : route('products.index');
                        $displayTitle = $itemTitle !== '' ? $itemTitle : ($series?->name ?? '');
                    @endphp

                    <a href="{{ $ctaUrl }}" class="collection-card">
                        @if($imagePath)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}" alt="{{ $displayTitle }}">
                        @endif
                        <div class="overlay" aria-hidden="true"></div>
                        <div>
                            @if($kicker)<div class="lbl">{{ $kicker }}</div>@endif
                            @if($displayTitle)<div class="name" style="margin-top: 8px;">{{ $displayTitle }}</div>@endif
                        </div>
                        <div>
                            @if($description)<div class="desc">{{ $description }}</div>@endif
                            @if($ctaLabel)
                                <div class="arrow" style="margin-top: 24px;">{{ $ctaLabel }} →</div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
