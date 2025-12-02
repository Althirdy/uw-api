<?php

namespace App\Events;

use App\Models\Citizen\Concern;
use App\Models\ConcernDistribution;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConcernStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $concern;
    public $distribution;
    public $previousStatus;
    public $newStatus;
    public $purokLeader;
    public $remarks;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Concern $concern,
        ConcernDistribution $distribution,
        string $previousStatus,
        string $newStatus,
        User $purokLeader,
        ?string $remarks = null
    ) {
        $this->concern = $concern;
        $this->distribution = $distribution;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->purokLeader = $purokLeader;
        $this->remarks = $remarks;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->concern->citizen_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'concern.status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Load necessary relationships if not already loaded
        $this->concern->loadMissing(['media', 'histories.actor.officialDetails']);

        return [
            'concern' => [
                'id' => $this->concern->id,
                'trackingCode' => $this->concern->tracking_code,
                'type' => $this->concern->type,
                'title' => $this->concern->title,
                'description' => $this->concern->description,
                'category' => $this->concern->category,
                'severity' => $this->concern->severity,
                'status' => $this->newStatus,
                'previousStatus' => $this->previousStatus,
                'latitude' => $this->concern->latitude,
                'longitude' => $this->concern->longitude,
                'address' => $this->concern->address,
                'customLocation' => $this->concern->custom_location,
                'transcriptText' => $this->concern->transcript_text,
                'citizenId' => $this->concern->citizen_id,
                'createdAt' => $this->concern->created_at?->toISOString(),
                'updatedAt' => $this->concern->updated_at?->toISOString(),
                
                // Media
                'images' => $this->concern->media
                    ->where('media_type', 'image')
                    ->pluck('original_path')
                    ->values()
                    ->toArray(),
                
                // Assigned Leader
                'assignedTo' => [
                    'id' => $this->purokLeader->id,
                    'name' => $this->purokLeader->officialDetails 
                        ? trim("{$this->purokLeader->officialDetails->first_name} {$this->purokLeader->officialDetails->last_name}") 
                        : $this->purokLeader->name,
                    'role' => $this->purokLeader->role->name ?? 'Purok Leader',
                ],
                
                // Timeline (Full History) - Matches ConcernResource structure
                'timeline' => $this->concern->histories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'status' => $history->status,
                        'remarks' => $history->remarks,
                        'actedBy' => $history->actor ? [
                            'id' => $history->actor->id,
                            'name' => $history->actor->officialDetails 
                                ? trim("{$history->actor->officialDetails->first_name} {$history->actor->officialDetails->last_name}")
                                : $history->actor->name,
                        ] : ['name' => 'UrbanWatch System'],
                        'createdAt' => $history->created_at->toISOString(),
                        'timeAgo' => $history->created_at->diffForHumans(),
                    ];
                })->values()->toArray(),
                
                // Latest history entry (for easy access)
                'latestUpdate' => [
                    'status' => $this->newStatus,
                    'remarks' => $this->remarks,
                    'actedBy' => [
                        'id' => $this->purokLeader->id,
                        'name' => $this->purokLeader->officialDetails 
                            ? trim("{$this->purokLeader->officialDetails->first_name} {$this->purokLeader->officialDetails->last_name}")
                            : $this->purokLeader->name,
                    ],
                    'createdAt' => now()->toISOString(),
                    'timeAgo' => now()->diffForHumans(),
                ],
            ],
            'distribution' => [
                'id' => $this->distribution->id,
                'status' => $this->distribution->status,
                'assignedAt' => $this->distribution->assigned_at,
                'acknowledgedAt' => $this->distribution->acknowledged_at,
            ],
        ];
    }
}
