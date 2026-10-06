<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalPasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public string $url,
        /** False the first time — distinguishes an invite from a recovery for the copy. */
        public bool $isReset,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isReset ? 'Reset your portal password' : 'Set up your member portal access',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.portal-password-reset',
            with: [
                'greeting' => $this->member->greetingName(),
                'companyName' => $this->member->company_name,
                'url' => $this->url,
                'isReset' => $this->isReset,
                'expireMinutes' => (int) config('auth.passwords.members.expire', 60),
            ],
        );
    }
}
