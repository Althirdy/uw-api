<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccidentResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'accident_type' => $this->accident_type,
            'status' => $this->status,
            'severity' => $this->severity,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'detected_at' => $this->detected_at?->toISOString(),
            'device_id' => $this->device_id,
            'confidence_score' => $this->confidence_score,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
