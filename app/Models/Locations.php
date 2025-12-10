<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Locations extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'location_name',
        'landmark',
        'barangay',
        'latitude',
        'longitude',
        'description',
    ];

    public function cctvDevices()
    {
        return $this->hasMany(cctvDevices::class, 'location_id');
    }
}
