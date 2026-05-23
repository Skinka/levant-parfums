@php($locale = app()->getLocale())
<section @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    @if(! empty($data['title']))
        <h2>{{ $data['title'][$locale] ?? $data['title']['uk'] ?? '' }}</h2>
    @endif
    <div>{!! Str::markdown($data['body'][$locale] ?? $data['body']['uk'] ?? '') !!}</div>
</section>
