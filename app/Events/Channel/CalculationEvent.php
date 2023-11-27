<?php

namespace App\Events\Channel;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalculationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $channel_id;
    private $program_id;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($channel_id, $program_id=0)
    {
        $this->channel_id = $channel_id;
        $this->program_id = $program_id;
    }

    public function getChannelId()
    {
        return $this->channel_id;
    }

    public function getChannelProgramId()
    {
        return $this->program_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-program');
    }
}
