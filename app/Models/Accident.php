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
        'cctv_device_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function media()
    {
        return $this->morphMany(IncidentMedia::class, 'source');
    }

    public function cctvDevice()
    {
        return $this->belongsTo(cctvDevices::class, 'cctv_device_id');
    }

    /**
     * Get location coordinates from the related CCTV device.
     *
     * @return array|null Returns ['latitude' => string, 'longitude' => string] or null
     */
    public function getLocationFromDevice()
    {
        if (! $this->cctvDevice || ! $this->cctvDevice->location) {
            return null;
        }

        $location = $this->cctvDevice->location;

        return [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'location_name' => $location->location_name,
            'barangay' => $location->barangay,
        ];
    }
}
