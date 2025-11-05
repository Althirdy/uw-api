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
    
<<<<<<< HEAD
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
=======
    public function media()
    {
        return $this->morphMany(IncidentMedia::class, 'source');
>>>>>>> ed05b14 (feat: Add Laravel Reverb real-time updates for YOLO accident detection)
    }
}
