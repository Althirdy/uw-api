<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConcernDistributionResource extends JsonResource
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
            'concern_id' => $this->concern_id,
            'purok_leader_id' => $this->purok_leader_id,
            'status' => $this->status,
            'assigned_at' => $this->assigned_at?->toISOString(),
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'purok_leader' => UserResource::make($this->whenLoaded('purokLeader')),
        ];
    }
}
