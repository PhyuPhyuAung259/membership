<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** What industry a member company is in. Staff-maintained reference data. */
class BusinessType extends Model
{
    protected $fillable = ['name'];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function scopeAlphabetical($q)
    {
        return $q->orderBy('name');
    }

    /**
     * Deleting a business type sets it to null on its members rather than
     * blocking, because an industry category is a label — unlike a tier,
     * nothing depends on it being present.
     */
    public function inUse(): bool
    {
        return $this->members()->exists();
    }
}
