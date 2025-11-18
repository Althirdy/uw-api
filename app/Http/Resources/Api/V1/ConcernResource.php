<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConcernResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'severity' => $this->severity,
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'transcript_text' => $this->transcript_text,
            'citizen_id' => $this->citizen_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'images' => $this->when(
                $this->relationLoaded('media'),
                fn() => $this->media->pluck('original_path')->toArray()
            ),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'distribution' => ConcernDistributionResource::make($this->whenLoaded('distribution')),
        ];
    }
}
