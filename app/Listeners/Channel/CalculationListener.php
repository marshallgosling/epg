<?php

namespace App\Listeners\Channel;

use App\Events\Channel\CalculationEvent;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Tools\ChannelGenerator;
use App\Tools\LoggerTrait;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CalculationListener
{
    use LoggerTrait;

    /**
     * Handle the event.
     *
     * @param  CalculationEvent  $event
     * @return void
     */
    public function handle(CalculationEvent $event)
    {
        $this->log_channel = 'program';
        $this->log_print = false;

        $channel = Channel::find($event->getChannelId());
        if($channel->audit_status = Channel::AUDIT_PASS) {
            $this->warn("Channel {$event->getChannelId()} is locked.");
            return;
        }
        $programs = $channel->programs()->get();

        $start = strtotime($channel->air_date . ' 06:00:00');
        $this->info("process program re-calculation: ".$event->getChannelId().' '.$event->getChannelProgramId());
        
        foreach($programs as $pro)
        {
            
                $items = json_decode($pro->data);
                $duration = 0;
                $pro->start_at = date('Y/m/d H:i:s', $start);
                foreach($items as $item) {
                    $duration += ChannelGenerator::parseDuration($item->duration);
                }
                $start += $duration;
                $pro->end_at = date('Y/m/d H:i:s', $start);
                $pro->duration = $duration;

                if($pro->isDirty()) {
                    $pro->version = $pro->version + 1;
                    $pro->save();
                    $this->info( "re-calculate {$pro->id} {$pro->name}, start:". $pro->start_at.' end:'. $pro->end_at.' duration:'.$pro->duration);
                }

        }
        $channel->version = $channel->version + 1;
        $channel->save();
    }
}
