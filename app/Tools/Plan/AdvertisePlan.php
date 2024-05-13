<?php
namespace App\Tools\Plan;

use App\Models\Channel;
use App\Models\Plan;
use App\Models\Notification;
use App\Models\TemplateRecords;
use App\Tools\ChannelGenerator;
use App\Tools\LoggerTrait;
use App\Tools\Notify;
use App\Events\Channel\CalculationEvent;

class AdvertisePlan
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

    public function __construct($group)
    {
        $this->log_channel = 'plan';
        $this->group = $group;
    }

    public function loadPlans()
    {
        $this->plans = Plan::where(['group_id'=>$this->group,'status'=>Plan::STATUS_RUNNING,'type'=>Plan::TYPE_ADVERTISE])->get();
    }

    public function filterPlan($channel, $template)
    {
        $air = strtotime($channel->air_date);
        $dayofweek = date('N', $air);
        //$plans = [];
        foreach($this->plans as $plan)
        {
            if(!in_array($dayofweek, explode(',', $plan->daysofweek))) continue;

            $begin = $plan->start_at ? strtotime($plan->start_at) : 0;
            $end = $plan->end_at ? strtotime($plan->end_at) : 999999999999;

            if($air < $begin || $air > $end) {
                //$this->lasterror = "{$plan->id} {$plan->category} 编排设定时间 {$plan->start_at}/{$plan->end_at} 已过期";
                continue;
            }

            if($plan->category == $template->id)
                return $plan;
        }

        return false;
    }

    private function loadPlanItem($plan)
    {
        $class = Channel::CLASSES[$plan->group_id];
        $program = $class::where('name', $plan->episodes)->first();
        return $program ? ChannelGenerator::createItem($program, $plan->category, '') : false;
    }

    private function scan($plan, $channel=false)
    {
        
        
    }

    private function apply($plan, $data)
    {
        

    }

    public function run($channel)
    {
        
    }
}