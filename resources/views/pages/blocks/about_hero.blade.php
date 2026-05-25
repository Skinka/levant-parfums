@php
    use Illuminate\Support\Facades\Storage;

    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };
    $eyebrow = $t('eyebrow');
    $title = $t('title');
    $lead = $t('lead');
    $body = $t('body');
    $stats = $data['stats'] ?? [];
    $imageUrl = ! empty($data['image_path']) ? Storage::disk('public')->url($data['image_path']) : null;
    $paragraphs = collect(preg_split("/\r?\n\r?\n/", trim((string) $body)))->filter()->values();
@endphp

<section class="about-hero reveal" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        <x-site.breadcrumbs :items="[
            ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
            ['label' => $page->title],
        ]"/>

        <div class="grid">
            <div class="copy">
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if($title)<h1>{{ $title }}</h1>@endif
                @if($lead)<p class="lead">{{ $lead }}</p>@endif
                @foreach($paragraphs as $p)
                    <p class="body">{!! nl2br(e($p)) !!}</p>
                @endforeach
            </div>
            <div class="img">
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $title }}"
                         width="800" height="800"
                         loading="eager" fetchpriority="high">
                @endif
            </div>
        </div>

        @if(! empty($stats))
            <div class="about-stats">
                @foreach($stats as $stat)
                    @php
                        $statLabel = ($stat['meta_label'][$locale] ?? null) ?: ($stat['meta_label']['uk'] ?? '');
                    @endphp
                    <div class="stat">
                        <div class="num">{{ $stat['num'] ?? '' }}</div>
                        <div class="lbl">{{ $statLabel }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
