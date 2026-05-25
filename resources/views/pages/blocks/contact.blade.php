@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };

    $eyebrow     = $t('eyebrow');
    $title       = $t('title');
    $lead        = $t('lead');
    $address     = $t('address');
    $hours       = $t('hours');
    $formEyebrow = $t('form_eyebrow');
    $formTitle   = $t('form_title');

    $phone     = $data['phone']      ?? '';
    $phoneHref = filled($data['phone_href'] ?? null) ? $data['phone_href'] : $phone;
    $email     = $data['email']      ?? '';
    $socials   = array_values(array_filter(
        $data['socials'] ?? [],
        fn ($s) => filled($s['label'] ?? null) && filled($s['url'] ?? null),
    ));

    $L = [
        'address' => __('content.blocks.contact.label_address'),
        'phone'   => __('content.blocks.contact.label_phone'),
        'email'   => __('content.blocks.contact.label_email'),
        'hours'   => __('content.blocks.contact.label_hours'),
        'social'  => __('content.blocks.contact.label_social'),
    ];
@endphp

<section class="contacts reveal" @if(!empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        <x-site.breadcrumbs :items="[
            ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
            ['label' => $page->title],
        ]"/>

        <div class="section-head">
            <div>
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if($title)<h1>{{ $title }}</h1>@endif
                @if($lead)<p class="lead">{{ $lead }}</p>@endif
            </div>
        </div>

        <div class="grid">
            <div class="info">
                @if(filled($address))
                    <div class="item">
                        <div class="l">{{ $L['address'] }}</div>
                        <div class="v">{{ $address }}</div>
                    </div>
                @endif

                @if(filled($phone))
                    <div class="item">
                        <div class="l">{{ $L['phone'] }}</div>
                        <div class="v"><a href="tel:{{ $phoneHref }}">{{ $phone }}</a></div>
                    </div>
                @endif

                @if(filled($email))
                    <div class="item">
                        <div class="l">{{ $L['email'] }}</div>
                        <div class="v"><a href="mailto:{{ $email }}">{{ $email }}</a></div>
                    </div>
                @endif

                @if(filled($hours))
                    <div class="item">
                        <div class="l">{{ $L['hours'] }}</div>
                        <div class="v">{{ $hours }}</div>
                    </div>
                @endif

                @if(!empty($socials))
                    <div class="item socials">
                        <div class="l">{{ $L['social'] }}</div>
                        <div class="links">
                            @foreach($socials as $s)
                                <a href="{{ $s['url'] }}" class="lnk lnk-mute"
                                   target="_blank" rel="noopener">{{ $s['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="form-card">
                @if($formEyebrow)<div class="eyebrow">{{ $formEyebrow }}</div>@endif
                @if($formTitle)<h2>{{ $formTitle }}</h2>@endif
                <livewire:contact-form />
            </div>
        </div>
    </div>
</section>
