<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        /** True when there's no document at all; false when it's just old. */
        public bool $isMissing,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isMissing
                ? 'Please upload your registration document'
                : 'Please confirm your registration document is up to date',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.document-reminder',
            with: [
                'greeting' => $this->member->greetingName(),
                'isMissing' => $this->isMissing,
                'portalUrl' => route('portal.documents'),
            ],
        );
    }
}
