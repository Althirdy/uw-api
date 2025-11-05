<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accident extends Model
{
    protected $fillable = [
        'title',
        'description',
        'latitude',
        'longitude',
        'occurred_at',
        'accident_type',
        'status',
        'severity',
    ];
    
    /**
     * Get all media for this accident (polymorphic relationship)
     */
    public function media()
    {
        return $this->morphMany(IncidentMedia::class, 'source');
    }
    
    /**
     * Legacy relationship name for backward compatibility
     */
    public function accidentMedia()
    {
        return $this->media();
    }
}
