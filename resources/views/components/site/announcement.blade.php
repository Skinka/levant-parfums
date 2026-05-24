@php($items = trans('site.announcement'))

<div class="announcement" role="presentation">
    <span class="marquee-track">
        @for($i = 0; $i < 2; $i++)
            @foreach($items as $item)
                <span class="marquee-item">{{ $item }}</span>
                <span class="marquee-sep" aria-hidden="true"></span>
            @endforeach
        @endfor
    </span>
</div>
