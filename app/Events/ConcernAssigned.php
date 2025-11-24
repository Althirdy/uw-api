<?php

namespace App\Events;

use App\Models\Citizen\Concern;
use App\Models\ConcernDistribution;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConcernAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $concern;
    public $distribution;
    public $images;
    public $audioUrl;

    /**
     * Create a new event instance.
     */
    public function __construct(Concern $concern, ConcernDistribution $distribution, array $images = [], ?string $audioUrl = null)
    {
        $this->concern = $concern;
        $this->distribution = $distribution;
        $this->images = $images;
        $this->audioUrl = $audioUrl;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('purok-leader.' . $this->distribution->purok_leader_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'concern.assigned';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'concern' => [
                'id' => $this->concern->id,
                'title' => $this->concern->title,
                'description' => $this->concern->description,
                'category' => $this->concern->category,
                'severity' => $this->concern->severity,
                'status' => $this->concern->status,
                'latitude' => $this->concern->latitude,
                'longitude' => $this->concern->longitude,
                'created_at' => $this->concern->created_at,
                'images' => $this->images,
                'audio' => $this->audioUrl,
                'summary' => $this->concern->summary,
                'transcript' => $this->concern->transcript_text,
            ],
            'distribution' => [
                'id' => $this->distribution->id,
                'status' => $this->distribution->status,
                'assigned_at' => $this->distribution->assigned_at,
            ],
            'citizen' => [
                'name' => $this->concern->citizen->name ?? 'Anonymous',
            ],
        ];
    }
}
