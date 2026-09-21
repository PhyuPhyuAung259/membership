<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = ['title', 'body', 'event_date', 'event_time', 'location', 'created_by'];

    protected $casts = ['event_date' => 'date:Y-m-d'];

    public function broadcasts(): HasMany
    {
        return $this->hasMany(Broadcast::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
