<?php

namespace App\Listeners\Channel;

use App\Events\Channel\ProgramsEvent;
use App\Models\ChannelPrograms;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProgramsListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ProgramsEvent $event)
    {
        $channel = ChannelPrograms::find($event->getChannelProgramId());
        $channel->exportXML();
    }
}
