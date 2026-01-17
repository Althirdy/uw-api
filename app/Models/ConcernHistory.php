<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcernHistory extends Model
{
    protected $fillable = [
        'concern_id',
        'acted_by',
        'status',
        'remarks',
    ];

    /**
     * Get the concern associated with this history.
     */
    public function concern(): BelongsTo
    {
        return $this->belongsTo(Citizen\Concern::class, 'concern_id');
    }

    /**
     * Get the user who performed the action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function getActorDisplayNameAttribute(): string
    {
        // 1. If there is no actor, it's the System
        if (! $this->actor) {
            return 'UrbanWatch System';
        }

        // 2. If they have official details, return "First Last"
        if ($this->actor->officialDetails) {
            return trim("{$this->actor->officialDetails->first_name} {$this->actor->officialDetails->last_name}");
        }

        // 3. Fallback to their login name
        return $this->actor->name;
    }
}
