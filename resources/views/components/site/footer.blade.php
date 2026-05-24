@props(['locale'])

<footer class="footer">
    <div class="container">
        <x-site.diamond-band class="mb-14" />

        <div class="grid">
            <div>
                <a href="{{ LaravelLocalization::localizeURL('/') }}" class="brand" style="align-items: flex-start">
                    <span>L E V A N T</span>
                    <span class="sub" style="letter-spacing: 1.2px">{{ __('site.brand_strapline') }}</span>
                </a>
                <p style="margin-top: 24px; color: var(--ink-soft); font-size: 14px; line-height: 1.7; max-width: 36ch;">
                    {{ __('site.footer.about') }}
                </p>
            </div>

            <div>
                <h4>{{ __('site.footer.columns.nav') }}</h4>
                <ul>
                    <li><a href="{{ LaravelLocalization::localizeURL('/') }}">{{ __('site.nav.home') }}</a></li>
                    <li><a href="{{ route('products.index') }}">{{ __('site.nav.catalog') }}</a></li>
                </ul>
            </div>

            <div>
                <h4>{{ __('site.footer.columns.shop') }}</h4>
                <ul>
                    <li><a href="{{ route('products.index', ['series' => 'onyx']) }}">{{ __('catalogue.public.filter_onyx') }}</a></li>
                    <li><a href="{{ route('products.index', ['series' => 'luxury']) }}">{{ __('catalogue.public.filter_luxury') }}</a></li>
                    <li><a href="{{ route('products.index', ['sort' => 'new']) }}">{{ __('site.footer.links.new') }}</a></li>
                    <li><a href="{{ route('products.index', ['sort' => 'pop']) }}">{{ __('site.footer.links.bestsellers') }}</a></li>
                </ul>
            </div>

            <div>
                <h4>{{ __('site.footer.columns.help') }}</h4>
                <ul>
                    <li><a href="#">{{ __('site.footer.links.delivery') }}</a></li>
                    <li><a href="#">{{ __('site.footer.links.returns') }}</a></li>
                    <li><a href="#">{{ __('site.footer.links.terms') }}</a></li>
                    <li><a href="#">{{ __('site.footer.links.privacy') }}</a></li>
                </ul>
            </div>

            <div>
                <h4>{{ __('site.footer.columns.contact') }}</h4>
                <ul>
                    <li><a href="tel:+380974128819">+38 (097) 412 88 19</a></li>
                    <li><a href="mailto:concierge@levant.parfum">concierge@levant.parfum</a></li>
                    <li><a href="#" rel="noopener">Instagram</a></li>
                    <li><a href="#" rel="noopener">Telegram</a></li>
                </ul>
            </div>
        </div>

        <div class="legal">
            <span>{{ __('site.footer.rights', ['year' => now()->year]) }}</span>
            <span>{{ __('site.footer.geo') }}</span>
        </div>
    </div>
</footer>
