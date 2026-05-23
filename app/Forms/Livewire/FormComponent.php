<?php

namespace App\Forms\Livewire;

use App\Forms\Models\FormSubmission;
use App\Forms\Support\FormRateLimiter;
use App\Forms\Types\FormType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Livewire\Component;

abstract class FormComponent extends Component
{
    public ?Model $subject = null;
    public string $hp = '';

    abstract protected function formType(): FormType;
    abstract public function render(): View;

    public function mount(?Model $subject = null): void
    {
        $type = $this->formType();

        if ($type->subjectRequired()) {
            $expected = $type->subjectClass();
            if (! $subject instanceof $expected) {
                throw new LogicException("Form {$type->key()} requires subject of type {$expected}");
            }
        }

        $this->subject = $subject;
    }

    public function submit(): void
    {
        $type = $this->formType();

        if ($this->hp !== '') {
            logger()->info('forms.honeypot.tripped', [
                'type' => $type->key(),
                'ip' => request()->ip(),
            ]);
            return;
        }

        FormRateLimiter::ensureAllowed($type, request());

        $data = $this->validate($type->rules($this->subject), [], $type->attributes());

        $submission = FormSubmission::create([
            'type' => $type->key(),
            'status' => FormSubmission::STATUS_NEW,
            'data' => $data,
            'subject_type' => $this->subject?->getMorphClass(),
            'subject_id' => $this->subject?->getKey(),
            'meta' => [
                'url' => url()->previous(),
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'referer' => request()->headers->get('referer'),
            ],
            'locale' => LaravelLocalization::getCurrentLocale() ?: config('app.fallback_locale'),
        ]);

        $this->dispatchEmails($type, $submission);

        $this->reset($this->resetableFields());
        session()->flash("forms.success.{$type->key()}", true);
        $this->dispatch('form-submitted', type: $type->key());
    }

    protected function dispatchEmails(FormType $type, FormSubmission $submission): void
    {
        $adminRecipients = $type->adminRecipients();
        if ($adminRecipients !== []) {
            Mail::to($adminRecipients)
                ->locale(config('app.fallback_locale'))
                ->queue($type->adminMailable($submission));
        }

        if ($clientMail = $type->clientMailable($submission)) {
            $field = $type->clientEmailField();
            $address = $field ? data_get($submission->data, $field) : null;
            if ($address) {
                Mail::to($address)
                    ->locale($submission->locale ?: config('app.fallback_locale'))
                    ->queue($clientMail);
            }
        }
    }

    protected function resetableFields(): array
    {
        return array_diff(
            array_keys(get_object_vars($this)),
            ['subject', 'hp'],
        );
    }
}
