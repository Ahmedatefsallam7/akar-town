<?php

namespace Modules\Careers\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Careers\Models\Applicant;

/**
 * Tells an applicant their application's new status (as Dastan meant to). In English and Arabic, since we don't know
 * which the applicant reads. Queued, so a mail problem never blocks HR's save.
 */
class ApplicationStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Applicant $applicant)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('admin.applicants.mail.subject', ['job' => $this->applicant->career?->title_en], 'en')
                .' | '.__('admin.applicants.mail.subject', ['job' => $this->applicant->career?->title_ar], 'ar'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'careers::mail.application-status');
    }
}
