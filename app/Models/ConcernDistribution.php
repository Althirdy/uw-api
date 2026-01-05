<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcernDistribution extends Model
{
    use HasFactory;

    protected $table = 'concern_distribution';

    protected $fillable = [
        'concern_id',
        'purok_leader_id',
        'status',
        'assigned_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Get the concern that is being distributed
     */
    public function concern(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Citizen\Concern::class, 'concern_id');
    }

    /**
     * Get the purok leader assigned to this concern
     */
    public function purokLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purok_leader_id');
    }
}
