@php
    $ids = collect($data['items'] ?? [])->pluck('article_id')->filter()->all();
    $articles = $ids
        ? \App\Models\Content\Article::query()->whereIn('id', $ids)->get()->keyBy('id')
        : collect();
@endphp
<section @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <ul>
        @foreach($ids as $id)
            @if($article = $articles[$id] ?? null)
                <li>{{ $article->title }}</li>
            @endif
        @endforeach
    </ul>
</section>
