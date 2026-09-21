<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A membership tier and its standard monthly fee.
 *
 * Changing monthly_fee here changes what every company on this tier is
 * charged from their next payment onward. It does NOT rewrite payments
 * already recorded, which is correct: those are money that actually changed
 * hands at the old price.
 */
class MemberType extends Model
{
    protected $fillable = ['name', 'description', 'monthly_fee', 'sort_order'];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** Tiers have a rank; alphabetical order gets it wrong. */
    public function scopeRanked($q)
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }

    /**
     * How many companies are on this tier. The database refuses to delete a
     * tier that is in use (restrictOnDelete), so check this before offering
     * a delete button.
     */
    public function inUse(): bool
    {
        return $this->members()->exists();
    }
}
