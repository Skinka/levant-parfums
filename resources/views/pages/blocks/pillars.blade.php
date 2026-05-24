@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };
    $eyebrow = $t('eyebrow');
    $title = $t('title');
    $body = $t('body');
    $items = $data['items'] ?? [];
    $surface = ($data['surface'] ?? 'default') === 'tinted' ? 'is-tinted' : '';
    $count = count($items);
    $sectionClass = trim("pillars reveal {$surface}");
@endphp

<section class="{{ $sectionClass }}" data-count="{{ $count }}" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        <div class="section-head">
            <div>
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if($title)<h2 style="margin-top: 12px;">{{ $title }}</h2>@endif
                @if($body)<p style="margin-top: 16px; max-width: 60ch; color: var(--ink-soft);">{{ $body }}</p>@endif
            </div>
        </div>

        @if(! empty($items))
            <div class="grid">
                @foreach($items as $i => $item)
                    @php
                        $itemEyebrow = ($item['eyebrow'][$locale] ?? null) ?: ($item['eyebrow']['uk'] ?? '');
                        $itemTitle = ($item['title'][$locale] ?? null) ?: ($item['title']['uk'] ?? '');
                        $itemBody = ($item['body'][$locale] ?? null) ?: ($item['body']['uk'] ?? '');
                        if ($itemEyebrow === '') {
                            $itemEyebrow = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                        }
                    @endphp
                    <x-site.pillar
                        variant="pillars"
                        :eyebrow="$itemEyebrow"
                        :title="$itemTitle"
                        :body="$itemBody"
                    />
                @endforeach
            </div>
        @endif
    </div>
</section>
