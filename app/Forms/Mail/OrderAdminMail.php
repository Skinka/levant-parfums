<?php

namespace App\Forms\Mail;

use App\Forms\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderAdminMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: trans('forms.mail.order.admin.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.forms.order-admin',
            with: ['s' => $this->submission],
        );
    }
}
