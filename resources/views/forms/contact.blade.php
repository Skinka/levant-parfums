<div>
@if (session('forms.success.contact'))
    <div class="form-success">
        <div class="ok" aria-hidden="true">✓</div>
        <h3>{{ __('forms.contact.thanks') }}</h3>
    </div>
@else
    <form wire:submit="submit" class="fields" novalidate>
        <x-forms.honeypot wire:model="hp" />

        @error('form')
            <div class="alert" data-testid="form-error">{{ $message }}</div>
        @enderror

        <label class="field">
            <span>{{ __('forms.fields.name') }} *</span>
            <input type="text" wire:model="name" autocomplete="name" required>
            @error('name') <span class="err" data-testid="name-error">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            <span>{{ __('forms.fields.email') }} *</span>
            <input type="email" wire:model="email" autocomplete="email" required>
            @error('email') <span class="err" data-testid="email-error">{{ $message }}</span> @enderror
        </label>

        <label class="field full">
            <span>{{ __('forms.fields.message') }} *</span>
            <textarea wire:model="message" rows="6" required></textarea>
            @error('message') <span class="err" data-testid="message-error">{{ $message }}</span> @enderror
        </label>

        <div class="actions">
            <span class="agree">{{ __('forms.contact.agree') }}</span>
            <button type="submit" class="btn">
                <span>{{ __('forms.contact.submit') }}</span>
                <span class="btn-arrow" aria-hidden="true">→</span>
            </button>
        </div>
    </form>
@endif
</div>
