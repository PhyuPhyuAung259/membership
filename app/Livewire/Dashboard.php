<?php

namespace App\Livewire;

use App\Models\EmailLog;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public ?string $reminderReport = null;

    /*
     * Computed properties, so each is queried once per request even when the
     * view touches it more than once.
     */

    #[Computed]
    public function counts(): array
    {
        return [
            'current' => Member::current()->count(),
            'due_soon' => Member::dueWithin(7)->count(),
            'overdue' => Member::overdue()->count(),
            'lapsed' => Member::lapsed()->count(),
            'total' => Member::count(),
        ];
    }

    #[Computed]
    public function overdueMembers()
    {
        // Ordered by paid_through ascending, which puts the longest-overdue
        // first: this list is a morning worklist, not a report.
        return Member::overdue()
            ->with('memberType')
            ->orderByRaw('paid_through ASC NULLS FIRST')
            ->orderBy('company_name')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function dueSoonMembers()
    {
        return Member::dueWithin(7)->with('memberType')->orderBy('paid_through')->orderBy('company_name')->limit(50)->get();
    }

    #[Computed]
    public function recentPayments()
    {
        return Payment::with('member.memberType')->latest('created_at')->limit(10)->get();
    }

    /**
     * Messages logged but never confirmed sent: a worker died mid-send.
     * Surfaced because nothing retries them automatically by design.
     */
    #[Computed]
    public function stuck()
    {
        return EmailLog::where('status', 'queued')
            ->where('created_at', '<', now()->subMinutes(10))
            ->latest()
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function emailActivity(): array
    {
        return [
            'reminders_30d' => EmailLog::where('kind', 'dues_reminder')->where('status', 'sent')
                ->where('created_at', '>', now()->subDays(30))->count(),
            'announcements_30d' => EmailLog::where('kind', 'announcement')->where('status', 'sent')
                ->where('created_at', '>', now()->subDays(30))->count(),
            'failed_7d' => EmailLog::where('status', 'failed')
                ->where('created_at', '>', now()->subDays(7))->count(),
        ];
    }

    /** Preview only: sends nothing, logs nothing, safe to click repeatedly. */
    public function previewReminders(): void
    {
        Artisan::call('dues:remind', ['--dry-run' => true, '--no-lapse' => true]);
        $this->reminderReport = trim(Artisan::output());
    }

    /**
     * Queue today's reminders by hand. The scheduler normally does this; the
     * button is for the first run and for catching up after an outage. Safe
     * to press twice: the dedupe keys absorb it.
     */
    public function runReminders(): void
    {
        Artisan::call('dues:remind');
        $this->reminderReport = trim(Artisan::output());

        unset($this->counts, $this->overdueMembers, $this->dueSoonMembers);

        session()->flash('status', 'Reminders queued. Make sure a queue worker is running.');
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
