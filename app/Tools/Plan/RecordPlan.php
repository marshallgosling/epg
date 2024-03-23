<?php

namespace App\Tools\Plan;

use App\Models\Category;
use App\Models\Channel;
use App\Models\Plan;
use App\Models\ChannelPrograms;
use App\Models\Material;
use App\Models\Notification;
use App\Models\TemplateRecords;
use App\Models\Record;
use App\Tools\ChannelGenerator;
use App\Tools\LoggerTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecordPlan
{
    use LoggerTrait;

    private $channel;
    private $plans;
    private $group;
    /**
     * 按24小时累加的播出时间，格式为 timestamp ，输出为 H:i:s
     */
    private $air;

    /**
     * 统计一档节目的时长，更换新节目时重新计算
     */
    private $duration;

    private $maxDuration = 0;

    public $errors = [];
    public $lasterror = '';

    public function __construct($group='xkc')
    {
        $this->log_channel = 'plan';
        $this->group = $group;
    }

    public function loadPlans()
    {
        $this->plans = Plan::where(['group_id'=>$this->group,'status'=>Plan::STATUS_RUNNING,'type'=>TemplateRecords::TYPE_STATIC])->get();
    }

    private function filterPlans()
    {
        $air = strtotime($this->channel->air_date);
        $dayofweek = date('N', $air);
        $plans = [];
        foreach($this->plans as $plan)
        {
            if(!in_array($dayofweek, $plan->dayofweek)) continue;

            $begin = $plan->date_from ? strtotime($plan->date_from) : 0;
            $end = $plan->date_to ? strtotime($plan->date_to) : 99999999999;

            if($end != 99999999999 && $end < time()) {
                $plan->status = Plan::STATUS_EXPIRED;
                $plan->save();
                continue;
            }

            if($air < $begin || $air > $end) {
                $this->lasterror = "{$plan->id} {$plan->category} 编排设定时间 {$plan->date_from}/{$plan->date_to} 已过期";
                continue;
            }

            $plans[] = $plan;
        }

        return $plans;
    }

    private function scan($plan, $channel=false)
    {
        if($channel) $this->channel = $channel;
        $channel = $this->channel;

        $programs = $channel->programs();

        $_channel = ["id"=>$channel->id, "date"=>$channel->air_date, "group"=>$channel->name, 'programs'=>[]];

        foreach($programs as $pro)
        {
            
            $items = json_decode($pro->data, true);

            $_program = ["id"=>$pro->id,"name"=>$pro->name,"start_at"=>$pro->start_at, 'items'=>[]];
                
            foreach($items as $idx=>$item) {
               // ToDo: 
                
            }

            $_channel['programs'] = $_program;
        }

        $data[$channel->name][] = $_channel;
        
    }

    private function apply($plan, $channel=false)
    {
        if($channel) $this->channel = $channel;
        $channel = $this->channel;


    }

    public function run($channel)
    {
        $this->channel = $channel;
        $plans = $this->filterPlans();

        if(count($plans) == 0) return;

        foreach($plans as $plan) {
            $data = $this->scan($plan);
        }
    }

    public static function init($plan)
    {
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

        return $plan;
    }
}