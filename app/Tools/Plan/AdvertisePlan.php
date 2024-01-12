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
        $this->plans = Plan::where(['group_id'=>$this->group,'status'=>Plan::STATUS_RUNNING,'type'=>TemplateRecords::TYPE_ADVERTISE])->get();
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
            $end = $plan->date_to ? strtotime($plan->date_to) : 999999999999;

            if($air < $begin || $air > $end) {
                $this->lasterror = "{$plan->id} {$plan->category} 编排设定时间 {$plan->date_from}/{$plan->date_to} 已过期";
                continue;
            }

            $plans[] = $plan;
        }

        return $plans;
    }

    private function loadPlanItem($plan)
    {
        $class = Channel::CLASSES[$plan->group_id];
        $program = $class::where('name', $plan->episodes)->first();
        return $program ? ChannelGenerator::createItem($program, $plan->category, '') : false;
    }

    private function scan($plan, $channel=false)
    {
        if($channel) $this->channel = $channel;
        $channel = $this->channel;

        $programs = $channel->programs()->get();

        $_channel = ["id"=>$channel->id, "date"=>$channel->air_date, "group"=>$channel->name, 'programs'=>[]];

        $start = strtotime($channel->air_date. ' '.$plan->start_at);
        $end = strtotime($channel->air_date. ' '.$plan->end_at);
        if($end < $start) $end += 86400;

        foreach($programs as $pro)
        {
            $air = strtotime($channel->air_date.' '.$pro->schedule_start_at);
            
            if($air >= $start && $air<=$end)
                $_channel['programs'][] = $pro;
        }

        return $_channel;
        
    }

    private function apply($plan, $data)
    {
        if(count($data['programs']) == 0) return;

        $id = rand(0, count($data['programs']));

        $item = $this->loadPlanItem($plan);

        if($item)
        {
            $programs = $data['prgrams'][$id];
            $data = json_decode($programs->data, true);
            $data[] = $item;
            $programs->data = json_encode($data);
            $programs->save();

            return true;
        }

        return false;

    }

    public function run($channel)
    {
        $this->channel = $channel;
        $plans = $this->filterPlans();

        if(count($plans) == 0) return;

        foreach($plans as $plan) {
            $data = $this->scan($plan);

            $ret = $this->apply($plan, $data);
            
            if($ret) {
                CalculationEvent::dispatch($channel->id);

                Notify::fireNotify(
                    $channel->name,
                    Notification::TYPE_GENERATE, 
                    "播出计划 {$plan->name} 匹配成功", 
                    "频道 {$channel->name} 日期 {$channel->air_date}".PHP_EOL."节目：{$data['name']}({$data['unique_no']})"
                );
            }
        }
    }
}