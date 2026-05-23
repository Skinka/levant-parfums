@php
    $ids = collect($data['items'] ?? [])->pluck('product_id')->filter()->all();
    $products = $ids
        ? \App\Models\Catalogue\Product::query()->whereIn('id', $ids)->get()->keyBy('id')
        : collect();
@endphp
<section @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <ul>
        @foreach($ids as $id)
            @if($product = $products[$id] ?? null)
                <li>{{ $product->name }}</li>
            @endif
        @endforeach
    </ul>
</section>
