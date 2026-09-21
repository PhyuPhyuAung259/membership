<?php

namespace App\Livewire;

use App\Jobs\SendAnnouncement;
use App\Mail\Announcement;
use App\Models\Broadcast;
use App\Models\Event;
use App\Models\Member;
use App\Services\LoggedMailer;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Announcements extends Component
{
    #[Url]
    public ?int $eventId = null;

    public string $subject = '';
    public string $body = '';
    public string $audience = 'all';

    public function mount(): void
    {
        // Arriving from the Events screen via "Announce" prefills the draft.
        if ($this->eventId && $event = Event::find($this->eventId)) {
            $this->subject = $event->title;
            $this->body = $event->body;
        }
    }

    /** Truthful count, after consent filtering, before anyone presses Send. */
    public function getRecipientCountProperty(): int
    {
        return Member::audience($this->audience)->count();
    }

    public function getExcludedCountProperty(): int
    {
        return Member::notCancelled()
            ->where(fn ($q) => $q->where('marketing_opt_in', false)->orWhereNotNull('unsubscribed_at'))
            ->count();
    }

    public function getBroadcastsProperty()
    {
        return Broadcast::with(['sentBy', 'event'])->latest()->limit(50)->get();
    }

    public function getEventsProperty()
    {
        return Event::orderByRaw('COALESCE(event_date, created_at::date) DESC')->limit(100)->get();
    }

    /**
     * Send one copy to the signed-in admin first. Worth doing every time:
     * email rendering is hard to predict and a bad announcement to 400
     * members cannot be recalled.
     */
    public function sendTest(LoggedMailer $mailer): void
    {
        $this->validate($this->rules());

        $admin = auth()->user();

        $draft = new Broadcast([
            'subject' => $this->subject,
            'body' => $this->body,
            'audience' => $this->audience,
            'event_id' => $this->eventId,
        ]);
        $draft->setRelation('event', $this->eventId ? Event::find($this->eventId) : null);

        $preview = new Member(['company_name' => $admin->name, 'email' => $admin->email]);

        $mailer->send(
            toEmail: $admin->email,
            mailable: new Announcement($preview, $draft, isTest: true),
            kind: 'announcement_test',
            // Unique per attempt, so a test can be re-sent as often as needed
            // while wording is being worked out.
            dedupeKey: 'test:' . $admin->id . ':' . now()->timestamp . ':' . random_int(1000, 9999),
            subject: '[Test] ' . $this->subject,
        );

        session()->flash('status', "Test sent to {$admin->email}.");
    }

    public function send(): void
    {
        $this->validate($this->rules());

        $recipients = Member::audience($this->audience)->get();

        if ($recipients->isEmpty()) {
            $this->addError('audience', 'That audience has nobody in it who can receive announcements.');

            return;
        }

        $broadcast = Broadcast::create([
            'subject' => $this->subject,
            'body' => $this->body,
            'audience' => $this->audience,
            'event_id' => $this->eventId,
            'queued_count' => $recipients->count(),
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);

        // One job per member: failures stay isolated, the queue paces itself,
        // and progress counts on this screen stay honest.
        foreach ($recipients as $member) {
            SendAnnouncement::dispatch($member, $broadcast);
        }

        $this->reset(['subject', 'body', 'eventId']);

        session()->flash('status', sprintf(
            'Queued for %d members. Delivery continues in the background — keep a queue worker running.',
            $recipients->count(),
        ));
    }

    private function rules(): array
    {
        return [
            'subject' => 'required|string|max:200',
            'body' => 'required|string|max:50000',
            'audience' => 'required|in:' . implode(',', array_keys(Broadcast::AUDIENCES)),
            'eventId' => 'nullable|integer|exists:events,id',
        ];
    }

    public function render()
    {
        return view('livewire.announcements', [
            'broadcasts' => $this->broadcasts,
            'events' => $this->events,
            'recipientCount' => $this->recipientCount,
            'excludedCount' => $this->excludedCount,
        ]);
    }
}
