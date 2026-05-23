<form wire:submit="submit">
    <x-forms.honeypot wire:model="hp" />

    @error('form') <div data-testid="form-error">{{ $message }}</div> @enderror

    <label>
        <span>{{ trans('forms.fields.name') }}</span>
        <input type="text" wire:model="name">
        @error('name') <span data-testid="name-error">{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.email') }}</span>
        <input type="email" wire:model="email">
        @error('email') <span data-testid="email-error">{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.message') }}</span>
        <textarea wire:model="message"></textarea>
        @error('message') <span data-testid="message-error">{{ $message }}</span> @enderror
    </label>

    <button type="submit">Submit</button>

    @if (session('forms.success.contact'))
        <div data-testid="form-success">Thanks</div>
    @endif
</form>
