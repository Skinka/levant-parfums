@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };
    $eyebrow = $t('eyebrow');
    $title = $t('title');
    $body = $t('body');
    $signature = $t('signature');
    $paragraphs = collect(preg_split("/\r?\n\r?\n/", trim($body)))->filter()->values();
@endphp

<section class="manifesto reveal" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        <div class="grid">
            <div>
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if($title)
                    <h2 style="margin-top: 16px;">
                        <span class="quote-open" aria-hidden="true">“</span>
                        {{ $title }}
                    </h2>
                @endif
            </div>
            <div class="body">
                @foreach($paragraphs as $p)
                    <p>{!! nl2br(e($p)) !!}</p>
                @endforeach
                @if($signature)
                    <p style="font-family: var(--font-serif); font-style: italic; color: var(--accent); margin-top: 28px;">{{ $signature }}</p>
                @endif
            </div>
        </div>
    </div>
</section>
