@if (session('forms.success.contact'))
    <div class="form-success">
        <div class="ok" aria-hidden="true">✓</div>
        <h3>{{ __('forms.contact.thanks') }}</h3>
    </div>
@else
    <form wire:submit="submit" class="fields" novalidate>
        <x-forms.honeypot wire:model="hp" />

        @error('form')
            <div class="form-error" data-testid="form-error">{{ $message }}</div>
        @enderror

        <div class="field">
            <label for="contact-name">{{ __('forms.fields.name') }} *</label>
            <input id="contact-name" type="text" wire:model="name" autocomplete="name" required>
            @error('name') <span class="field-error" data-testid="name-error">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="contact-email">{{ __('forms.fields.email') }} *</label>
            <input id="contact-email" type="email" wire:model="email" autocomplete="email" required>
            @error('email') <span class="field-error" data-testid="email-error">{{ $message }}</span> @enderror
        </div>

        <div class="field full">
            <label for="contact-message">{{ __('forms.fields.message') }} *</label>
            <textarea id="contact-message" wire:model="message" rows="6" required></textarea>
            @error('message') <span class="field-error" data-testid="message-error">{{ $message }}</span> @enderror
        </div>

        <div class="actions">
            <span class="agree">{{ __('forms.contact.agree') }}</span>
            <button type="submit" class="btn">
                <span>{{ __('forms.contact.submit') }}</span>
                <span class="btn-arrow" aria-hidden="true">→</span>
            </button>
        </div>
    </form>
@endif
