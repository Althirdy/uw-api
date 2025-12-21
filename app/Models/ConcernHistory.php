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
}
