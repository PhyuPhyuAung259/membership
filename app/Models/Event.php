<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    protected $fillable = ['title', 'body', 'image_path', 'event_date', 'event_time', 'location', 'created_by'];

    protected $casts = ['event_date' => 'date:Y-m-d'];

    public function broadcasts(): HasMany
    {
        return $this->hasMany(Broadcast::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Public URL for the event's banner image, or null when it has none. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
