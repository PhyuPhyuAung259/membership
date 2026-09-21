<?php

namespace App\Models;

use App\Services\BillingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A member COMPANY on the register.
 */
class Member extends Model
{
    protected $fillable = [
        'company_name', 'business_type_id', 'email', 'phone',
        'contact_person', 'contact_person_position',
        'member_type_id', 'status', 'monthly_fee', 'join_date',
        'marketing_opt_in', 'unsubscribed_at', 'notes',
        'logo_path', 'registration_document_path',
    ];

    // paid_through is maintained by a database trigger. Keeping it out of
    // $fillable stops a stray ->update() from fighting the trigger.
    protected $guarded = ['paid_through'];

    protected $casts = [
        'join_date' => 'date:Y-m-d',
        'paid_through' => 'date:Y-m-d',
        'unsubscribed_at' => 'datetime',
        'marketing_opt_in' => 'boolean',
        'monthly_fee' => 'decimal:2',
    ];

    // Mirrors the database defaults so a freshly-made, unrefreshed model reads
    // the same as one loaded back from Postgres.
    protected $attributes = [
        'status' => 'active',
        'marketing_opt_in' => true,
    ];

    /* ---------------------------------------------------------- relations */

    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class);
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort_order')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /* --------------------------------------------------------------- fees */

    /**
     * What this company actually pays each month.
     *
     * The tier sets the price; monthly_fee on the member is an override that
     * is normally null. Read the fee through here everywhere — a bare
     * $member->monthly_fee is null for most companies and will quietly
     * produce a zero on an invoice or in a reminder email.
     *
     * Eager-load memberType wherever you list members, or this costs a query
     * per row.
     */
    public function effectiveMonthlyFee(): float
    {
        if ($this->monthly_fee !== null) {
            return (float) $this->monthly_fee;
        }

        return (float) ($this->memberType?->monthly_fee ?? 0);
    }

    /** True when this company is on a negotiated rate, not the tier price. */
    public function hasCustomFee(): bool
    {
        return $this->monthly_fee !== null;
    }

    /* ------------------------------------------------------------ billing */

    /** First day this company is not paid for. */
    public function dueOn(): string
    {
        return BillingPeriod::dueOn(
            $this->paid_through?->format('Y-m-d'),
            $this->join_date->format('Y-m-d'),
        );
    }

    /** Days since the due date. Negative means not due yet. */
    public function dayOffset(): int
    {
        return BillingPeriod::dayOffset($this->dueOn());
    }

    public function daysOverdue(): int
    {
        return max(0, $this->dayOffset());
    }

    public function billingState(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->paid_through === null) {
            return 'never_paid';
        }

        // Strictly less than zero: a company whose coverage ends TODAY is
        // still current. This matches the SQL scopes below, which compare
        // paid_through >= CURRENT_DATE.
        return $this->dayOffset() < 0 ? 'current' : 'overdue';
    }

    public function billingLabel(): string
    {
        return [
            'current' => 'Paid up',
            'overdue' => 'Overdue',
            'never_paid' => 'Never paid',
            'cancelled' => 'Cancelled',
        ][$this->billingState()] ?? $this->billingState();
    }

    /* ------------------------------------------------------------- people */

    /**
     * Who to greet in an email. The contact person's first name when we have
     * one, otherwise the company itself — "Hello Acme Trading" is stiff but
     * never wrong, which is the right failure for a dues notice.
     */
    public function greetingName(): string
    {
        if (filled($this->contact_person)) {
            return explode(' ', trim($this->contact_person))[0];
        }

        return $this->company_name;
    }

    /* ------------------------------------------------------------ scopes */

    /*
     * These mirror billingState() in SQL so lists can be filtered and paged
     * without loading every member into PHP. The duplication is real, so
     * tests/Feature/BillingConsistencyTest.php asserts the scopes and the
     * accessor always agree. If you change one, change the other and run it.
     */

    public function scopeNotCancelled(Builder $q): Builder
    {
        return $q->where('status', '!=', 'cancelled');
    }

    public function scopeCurrent(Builder $q): Builder
    {
        return $q->notCancelled()->whereNotNull('paid_through')
            ->whereRaw('paid_through >= CURRENT_DATE');
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->notCancelled()->where(function (Builder $q) {
            $q->whereNull('paid_through')->orWhereRaw('paid_through < CURRENT_DATE');
        });
    }

    public function scopeDueWithin(Builder $q, int $days): Builder
    {
        return $q->current()->whereRaw('paid_through < CURRENT_DATE + ?::integer', [$days]);
    }

    public function scopeLapsed(Builder $q): Builder
    {
        return $q->where('status', 'lapsed');
    }

    /** Company name, contact person, email or phone. */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) {
            return $q;
        }

        $like = '%' . mb_strtolower($term) . '%';

        return $q->where(function (Builder $q) use ($like) {
            $q->whereRaw('lower(company_name) like ?', [$like])
                ->orWhereRaw('lower(coalesce(contact_person, \'\')) like ?', [$like])
                ->orWhereRaw('lower(email) like ?', [$like])
                ->orWhere('phone', 'like', $like);
        });
    }

    /** Recipients for an announcement, after consent filtering. */
    public function scopeAudience(Builder $q, string $audience): Builder
    {
        $q = match ($audience) {
            'active' => $q->where('status', 'active'),
            'current' => $q->current(),
            'overdue' => $q->overdue(),
            'lapsed' => $q->lapsed(),
            default => $q->notCancelled(),
        };

        return $q->where('marketing_opt_in', true)
            ->whereNull('unsubscribed_at')
            ->where('email', '!=', '');
    }

    /* ----------------------------------------------------------- consent */

    /**
     * Announcements are marketing and need opt-in. Dues reminders are
     * transactional: they concern money owed under an existing agreement, so
     * they still go to a company that opted out of announcements. Cancelled
     * members get nothing at all.
     *
     * This is the one place that decision lives. Have whoever handles your
     * compliance confirm it matches your jurisdiction before going live.
     *
     * @return array{ok: bool, reason: string|null}
     */
    public function canReceive(string $kind): array
    {
        if (blank($this->email)) {
            return ['ok' => false, 'reason' => 'no email address'];
        }

        if ($this->status === 'cancelled') {
            return ['ok' => false, 'reason' => 'membership cancelled'];
        }

        if ($kind === 'announcement') {
            if ($this->unsubscribed_at !== null) {
                return ['ok' => false, 'reason' => 'unsubscribed'];
            }

            if (! $this->marketing_opt_in) {
                return ['ok' => false, 'reason' => 'not opted in to announcements'];
            }
        }

        return ['ok' => true, 'reason' => null];
    }
}
