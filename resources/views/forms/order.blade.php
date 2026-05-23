<form wire:submit="submit">
    <x-forms.honeypot wire:model="hp" />

    @error('form') <div data-testid="form-error">{{ $message }}</div> @enderror

    @if ($subject)
        <p data-testid="subject-name">{{ $subject->name ?? $subject->getKey() }}</p>
    @endif

    <label>
        <span>{{ trans('forms.fields.name') }}</span>
        <input type="text" wire:model="name">
        @error('name') <span>{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.phone') }}</span>
        <input type="text" wire:model="phone">
        @error('phone') <span>{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.email') }}</span>
        <input type="email" wire:model="email">
        @error('email') <span>{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.qty') }}</span>
        <input type="number" min="1" wire:model="qty">
        @error('qty') <span>{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.note') }}</span>
        <textarea wire:model="note"></textarea>
    </label>

    <button type="submit">Submit</button>

    @if (session('forms.success.order'))
        <div data-testid="form-success">Thanks</div>
    @endif
</form>
