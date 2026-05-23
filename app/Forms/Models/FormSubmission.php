<?php

namespace App\Forms\Models;

use Database\Factories\Forms\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormSubmission extends Model
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    protected static function newFactory(): FormSubmissionFactory
    {
        return FormSubmissionFactory::new();
    }

    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_PROCESSED = 'processed';

    public const STATUSES = [self::STATUS_NEW, self::STATUS_READ, self::STATUS_PROCESSED];

    protected $fillable = [
        'type', 'status', 'data', 'subject_type', 'subject_id', 'meta', 'locale', 'handled_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_NEW,
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'meta' => 'array',
            'handled_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function markRead(): void
    {
        if ($this->status === self::STATUS_NEW) {
            $this->update(['status' => self::STATUS_READ]);
        }
    }

    public function markProcessed(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'handled_at' => now(),
        ]);
    }

    public function markNew(): void
    {
        $this->update([
            'status' => self::STATUS_NEW,
            'handled_at' => null,
        ]);
    }
}
