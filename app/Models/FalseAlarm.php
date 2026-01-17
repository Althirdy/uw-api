<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FalseAlarm extends Model
{
    protected $fillable = [
        'cctv_device_id',
        'attempted_accident_type',
        'gemini_reasoning',
        'confidence_score',
        'detected_objects',
        'gemini_metadata',
        'detected_at',
    ];

    protected $casts = [
        'detected_objects' => 'array',
        'gemini_metadata' => 'array',
        'confidence_score' => 'decimal:2',
        'detected_at' => 'datetime',
    ];

    /**
     * Get the CCTV device that triggered this false alarm.
     */
    public function cctvDevice()
    {
        return $this->belongsTo(cctvDevices::class, 'cctv_device_id');
    }

    /**
     * Scope to get false alarms from today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get false alarms from this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope to get false alarms from this hour.
     */
    public function scopeThisHour($query)
    {
        return $query->whereBetween('created_at', [now()->startOfHour(), now()->endOfHour()]);
    }

    /**
     * Create a false alarm record from YOLO detection data.
     */
    public static function createFromDetection(int $cctvDeviceId, array $aiAnalysis): self
    {
        return self::create([
            'cctv_device_id' => $cctvDeviceId,
            'attempted_accident_type' => $aiAnalysis['accident_type'] ?? null,
            'gemini_reasoning' => $aiAnalysis['reasoning'] ?? 'No reasoning provided',
            'confidence_score' => $aiAnalysis['confidence'] ?? null,
            'detected_objects' => $aiAnalysis['detected_objects'] ?? [],
            'gemini_metadata' => $aiAnalysis,
            'detected_at' => now(),
        ]);
    }
}
