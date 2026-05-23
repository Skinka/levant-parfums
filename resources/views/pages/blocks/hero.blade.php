@php($locale = app()->getLocale())
<section @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <h1>{{ $data['title'][$locale] ?? $data['title']['uk'] ?? '' }}</h1>
    @if(! empty($data['subtitle']))
        <p>{{ $data['subtitle'][$locale] ?? $data['subtitle']['uk'] ?? '' }}</p>
    @endif
    @if($path = ($data['image_path'] ?? null))
        <img src="{{ Storage::disk('public')->url($path) }}" alt="">
    @endif
    @if(! empty($data['cta_url']) && ! empty($data['cta_label']))
        <a href="{{ $data['cta_url'] }}">{{ $data['cta_label'][$locale] ?? $data['cta_label']['uk'] }}</a>
    @endif
</section>
