<?php

namespace App\Listeners;

use App\Events\PlanEvent;
use App\Models\Plan;
use App\Models\Record;
use App\Models\TemplateRecords;
use App\Tools\ChannelGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PlanListener
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
     * @param  PlanEvent  $event
     * @return void
     */
    public function handle(PlanEvent $event)
    {
        $plan = $event->getPlan();

        $begin = $plan->date_from ? strtotime($plan->date_from) : 0;
        $end = $plan->date_to ? strtotime($plan->date_to) : 0;

        if($begin == 0 || $end == 0) return;
        $lastEpisode = '';
        $items = [];

        for(;$begin<=$end;$begin+=86400)
        {
            $dayofweek = date('N', $begin);
            if(!in_array($dayofweek, $plan->dayofweek)) continue;

            if($plan->type == TemplateRecords::TYPE_STATIC) {
                $episode = $plan->episodes;

                $item = Record::findNextEpisode($episode, $lastEpisode);

                if(in_array($item, ['finished', 'empty'])) break;

                $items[] = ChannelGenerator::createItem($item, $plan->category, date('Y-m-d ', $begin).$plan->start_at);
            }
        }

        $plan->data = json_encode($items);
        $plan->save();
    }
}
