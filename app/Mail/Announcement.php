<?php

namespace App\Mail;

use App\Models\Broadcast;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class Announcement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public Broadcast $broadcast,
        public bool $isTest = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->isTest ? '[Test] ' : '') . $this->broadcast->subject,
            replyTo: config('membership.reply_to')
                ? [new Address(config('membership.reply_to'))]
                : [],
        );
    }

    public function content(): Content
    {
        $event = $this->broadcast->event;

        return new Content(
            markdown: 'mail.announcement',
            with: [
                'heading' => $this->broadcast->subject,
                'bodyText' => $this->broadcast->body,
                'event' => $event,
                'eventImageUrl' => $event?->imageUrl(),
                'eventWhen' => $event
                    ? trim(collect([
                        $event->event_date?->format('j F Y'),
                        $event->event_time,
                    ])->filter()->implode(', '))
                    : null,
                'unsubscribeUrl' => $this->unsubscribeUrl(),
            ],
        );
    }

    /**
     * A signed, expiring URL rather than a token stored on the member row.
     * Laravel verifies the signature itself, so there is no column to keep in
     * step and no token to leak in a database dump.
     */
    private function unsubscribeUrl(): string
    {
        if ($this->isTest) {
            return URL::to('/unsubscribe/test');
        }

        return URL::temporarySignedRoute(
            'unsubscribe.show',
            now()->addDays(config('membership.unsubscribe_link_days')),
            ['member' => $this->member->id],
        );
    }
}
