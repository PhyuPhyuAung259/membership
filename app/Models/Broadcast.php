<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model
{
    protected $fillable = [
        'subject', 'body', 'audience', 'event_id',
        'queued_count', 'sent_count', 'failed_count', 'sent_by', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public const AUDIENCES = [
        'all' => 'Everyone on the register',
        'active' => 'Active members only',
        'current' => 'Members who are paid up',
        'overdue' => 'Members who are behind',
        'lapsed' => 'Lapsed members',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function isFinished(): bool
    {
        return $this->sent_count + $this->failed_count >= $this->queued_count;
    }
}
