<?php

namespace App\Events;

use App\Models\Citizen\Concern;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConcernTranscribed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $concern;

    /**
     * Create a new event instance.
     */
    public function __construct(Concern $concern)
    {
        $this->concern = $concern;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Notify the citizen who created the concern
        if ($this->concern->citizen_id) {
            $channels[] = new PrivateChannel('citizen.' . $this->concern->citizen_id);
        }

        // Notify the assigned purok leader
        // We need to fetch the active distribution to find the purok leader
        $distribution = $this->concern->distribution;
        if ($distribution && $distribution->purok_leader_id) {
            $channels[] = new PrivateChannel('purok-leader.' . $distribution->purok_leader_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'concern.transcribed';
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
                'transcript_text' => $this->concern->transcript_text,
                'status' => $this->concern->status,
                'updated_at' => $this->concern->updated_at,
            ],
        ];
    }
}
