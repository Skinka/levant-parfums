<?php

namespace App\Forms\Types;

use App\Forms\Models\FormSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;

abstract class FormType
{
    /** Stable machine key — goes into form_submissions.type, rate-limit keys, mail view names. */
    abstract public function key(): string;

    /** Translated, human-readable label for admin UI. */
    abstract public function label(): string;

    /** Laravel validation rules. $subject is the polymorphic Eloquent model (or null). */
    abstract public function rules(?Model $subject = null): array;

    /** Translated attribute names for validator messages (field => label). */
    public function attributes(): array
    {
        return [];
    }

    /** If the form requires a subject, return its class name (e.g. Product::class). */
    public function subjectClass(): ?string
    {
        return null;
    }

    public function subjectRequired(): bool
    {
        return false;
    }

    /** Recipients of the admin email. */
    public function adminRecipients(): array
    {
        return array_filter([config('forms.admin_email')]);
    }

    /** Mailable for the admin notification. Required. */
    abstract public function adminMailable(FormSubmission $submission): Mailable;

    /** Mailable for the client confirmation. Return null to skip. */
    public function clientMailable(FormSubmission $submission): ?Mailable
    {
        return null;
    }

    /** Field name in $submission->data that contains the client's email; null disables client mail. */
    public function clientEmailField(): ?string
    {
        return 'email';
    }

    /** [attempts, decay_minutes] for RateLimiter, keyed by type + IP. */
    public function rateLimit(): array
    {
        return [5, 60];
    }

    /**
     * Per-type fields merged into FormSubmission::$data at submit time.
     * Use to snapshot state from $subject (e.g. is_preorder).
     */
    public function metadata(?Model $subject): array
    {
        return [];
    }
}
