<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight resource for map markers.
 *
 * Returns minimal data needed to render accident markers on a heatmap/map.
 * Full details should be fetched via the show endpoint when user clicks a marker.
 */
class AccidentMarkerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accident_type' => $this->accident_type,
            'severity' => $this->severity,
        ];
    }
}
