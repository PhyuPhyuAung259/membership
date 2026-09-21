<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $table = 'email_log';

    protected $fillable = [
        'member_id', 'to_email', 'kind', 'dedupe_key', 'subject',
        'broadcast_id', 'status', 'mailer', 'error', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function kindLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->kind));
    }
}
