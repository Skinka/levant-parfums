@php
    $isPreorder = ! ($subject?->in_stock ?? true);
    $titleKey = $isPreorder ? 'preorder' : 'order';
@endphp

<div>
@if (session('forms.success.order'))
    @php($latestId = \App\Forms\Models\FormSubmission::query()->latest('id')->value('id'))
    <div class="order-thanks">
        <div class="ok">✓</div>
        <h3>{{ trans("forms.order.thanks.{$titleKey}") }}</h3>
        @if($latestId)
            <p class="number">LV-{{ str_pad((string) $latestId, 4, '0', STR_PAD_LEFT) }}</p>
        @endif
    </div>
@else
    <form class="order-form" wire:submit="submit">
        <x-forms.honeypot wire:model="hp" />

        @error('form') <div class="alert" data-testid="form-error">{{ $message }}</div> @enderror

        <div class="intro">
            <div class="eyebrow">{{ trans("forms.order.eyebrow.{$titleKey}") }}</div>
            <h2>{{ trans("forms.order.title.{$titleKey}") }}</h2>
            <p>{{ trans("forms.order.intro.{$titleKey}") }}</p>

            @if($subject)
                <div class="summary">
                    @if($subject->getFirstMediaUrl('primary', 'thumb'))
                        <div class="img"><img src="{{ $subject->getFirstMediaUrl('primary', 'thumb') }}" alt=""></div>
                    @endif
                    <div>
                        <div class="title" data-testid="subject-name">{{ $subject->name }}</div>
                        <div class="meta">{{ $subject->volume_ml }} ml · eau de parfum</div>
                        @php($price = $subject->displayPrice())
                        <div class="price">{{ number_format((float) $price['amount'], 0, ',', ' ') }} {{ $price['currency'] }}</div>

                        <div class="qty-stepper">
                            <span class="l">{{ trans('forms.fields.qty') }}</span>
                            <button type="button" wire:click="decrement" aria-label="−">−</button>
                            <span class="v">{{ $qty }}</span>
                            <button type="button" wire:click="increment" aria-label="+">+</button>
                        </div>

                        <div class="subtotal">
                            <span class="l">{{ trans('forms.order.subtotal') }}</span>
                            <span class="v">{{ number_format($this->subtotal, 0, ',', ' ') }} {{ $price['currency'] }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="fields">
            <label class="field full">
                <span>{{ trans('forms.fields.name') }} *</span>
                <input type="text" wire:model="name" required>
                @error('name') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                <span>{{ trans('forms.fields.phone') }} *</span>
                <input type="tel" wire:model="phone" required>
                @error('phone') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                <span>{{ trans('forms.fields.email') }} *</span>
                <input type="email" wire:model="email" required>
                @error('email') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                <span>{{ trans('forms.fields.city') }} *</span>
                <input type="text" wire:model="city" required>
                @error('city') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                <span>{{ trans('forms.fields.np_office') }} *</span>
                <input type="text" wire:model="np_office" required>
                @error('np_office') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field full">
                <span>{{ trans('forms.fields.comment') }}</span>
                <textarea wire:model="comment" rows="3"></textarea>
            </label>

            <div class="actions">
                <span class="agree">{{ trans('forms.order.agree') }}</span>
                <button type="submit" class="btn">
                    <span>{{ trans("forms.order.submit.{$titleKey}") }}</span>
                    <span class="btn-arrow" aria-hidden="true">→</span>
                </button>
            </div>
        </div>
    </form>
@endif
</div>
