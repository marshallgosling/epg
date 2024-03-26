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
        if($channel->lock_status == Channel::LOCK_ENABLE) {
            // $this->warn("Channel {$event->getChannelId()} is locked.");
            // return;
            $channel->lock_status = Channel::LOCK_EMPTY;
            //$channel->save();
        }
        $programs = $channel->programs()->get();

        $start = $channel->name == 'xkv' ? strtotime($channel->air_date . ' 17:00:00') : strtotime($channel->air_date . ' 17:00:00');
        $this->info("process program re-calculation: ".$event->getChannelId().' '.$event->getChannelProgramId());
        $start_end = date('H:i:s', $start);
        foreach($programs as $pro)
        {
            $items = json_decode($pro->data);
            if(array_key_exists('replicate', $items))
            {
                $duration = (int) ChannelPrograms::where('id', $items->replicate)->value('duration');
                $this->info( "replicate {$items->replicate} {$pro->name}, duration: $duration");
            }
            else {
                $duration = 0;
                
                foreach($items as $item) {
                    $duration += ChannelGenerator::parseDuration($item->duration);
                }
            }

            $pro->start_at = date('Y/m/d H:i:s', $start);
            $start += $duration;
            $pro->end_at = date('Y/m/d H:i:s', $start);
            $pro->duration = $duration;

            if($pro->isDirty()) {
                $pro->version = $pro->version + 1;
                $pro->save();
                $this->info( "re-calculate {$pro->id} {$pro->name}, start:". $pro->start_at.' end:'. $pro->end_at.' duration:'.$pro->duration);
            }

        }
        
        $channel->start_end = $start_end . ' - '. date('H:i:s', $start);
        $channel->comment = ChannelGenerator::checkAbnormalTimespan($start);
        if($channel->isDirty())
        {
            $channel->version = $channel->version + 1;
            $channel->status = Channel::STATUS_READY;
            $channel->save();
        }
    }
}
