<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CategoryRelationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $program_id;
    public $categorys;
    public $table;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($program_id, $categorys, $table)
    {
        $this->program_id = $program_id;
        $this->categorys = $categorys;
        $this->table = $table;
    }

}
