<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'member_id', 'amount', 'paid_on', 'period_start', 'period_end',
        'method', 'reference', 'note', 'recorded_by',
    ];

    protected $casts = [
        'paid_on' => 'date:Y-m-d',
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public const METHODS = ['bank_transfer', 'cash', 'cheque', 'card', 'other'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function methodLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->method));
    }
}
