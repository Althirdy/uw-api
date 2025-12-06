<?php

namespace App\Models\Citizen;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\IncidentMedia;
use App\Models\User;
use App\Models\ConcernDistribution;
use App\Models\ConcernHistory;

class Concern extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'citizen_id',
        'tracking_code',
        'title',
        'type',
        'description',
        'category',
        'status',
        'transcript_text',
        'longitude',
        'latitude',
        'address',
        'custom_location',
        'severity',
    ];

    protected $casts = [
        'longitude' => 'decimal:7',
        'latitude' => 'decimal:7',
    ];

    public function media()
    {
        return $this->morphMany(IncidentMedia::class, 'source');
    }

    public function citizen()
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function distribution()
    {
        return $this->hasOne(ConcernDistribution::class, 'concern_id');
    }

    public function histories()
    {
        return $this->hasMany(ConcernHistory::class, 'concern_id')->orderBy('created_at', 'desc');
    }
}
