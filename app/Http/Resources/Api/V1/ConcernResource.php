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
        // Determine assigned leader from the distribution relationship
        $assignedLeader = null;
        if ($this->relationLoaded('distribution') && $this->distribution) {
            $assignedLeader = $this->distribution->purokLeader;
        }

        return [
            'id' => $this->id,
            'trackingCode' => $this->tracking_code,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'severity' => $this->severity,
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address, // Add address
            'customLocation' => $this->custom_location, // Add custom_location
            'transcriptText' => $this->transcript_text,
            'citizenId' => $this->citizen_id,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            
            // Media
            'images' => $this->when(
                $this->relationLoaded('media'),
                fn() => $this->media->pluck('original_path')->toArray()
            ),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            
            // Assigned Leader (Transparency)
            'assignedTo' => $assignedLeader ? [
                'id' => $assignedLeader->id,
                'name' => $assignedLeader->officialDetails 
                    ? trim("{$assignedLeader->officialDetails->first_name} {$assignedLeader->officialDetails->last_name}") 
                    : $assignedLeader->name,
                'role' => $assignedLeader->role->name ?? 'Purok Leader',
                // 'avatar' => ... 
            ] : null,

            // Timeline (Audit History)
            'timeline' => $this->whenLoaded('histories', function () {
                return $this->histories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'status' => $history->status,
                        'remarks' => $history->remarks,
                        'actedBy' => $history->actor ? [
                            'id' => $history->actor->id,
                            'name' => $history->actor->officialDetails 
                                ? trim("{$history->actor->officialDetails->first_name} {$history->actor->officialDetails->last_name}")
                                : $history->actor->name,
                        ] : ['name' => 'UrbanWatch System'], // If null, it's UrbanWatch System
                        'createdAt' => $history->created_at->toISOString(),
                        'timeAgo' => $history->created_at->diffForHumans(),
                    ];
                });
            }),
        ];
    }
}