<?php
namespace App\Tools\Simulator;

use App\Models\Channel;
use App\Models\Template;
use App\Models\ChannelPrograms;
use App\Models\Notification;
use App\Models\Record;
use App\Models\TemplateRecords;
use App\Tools\ChannelDatabase;
use App\Tools\ChannelGenerator;
use App\Tools\Generator\XkcGenerator;
use Illuminate\Support\Facades\DB;
use App\Models\Plan;
use App\Tools\LoggerTrait;
use App\Tools\Notify;
use App\Tools\Plan\AdvertisePlan;
use Illuminate\Support\Facades\Storage;

class XkcSimulator
{
    use LoggerTrait;

    /**
     * 空编单列表
     */
    private $channels;
    private $programs;
    private $templates;
    private $group;
    /**
     * 按24小时累加的播出时间，格式为 timestamp ，输出为 H:i:s
     */
    private $days;
    public $errors;
    /**
     * 是否保存状态
     */
    private $saveState = false;
    /**
     * 统计一档节目的时长，更换新节目时重新计算
     */
    private $duration;

    private $plans;

    public $filename;

    public function __construct($group, $days, $channels=false)
    {
        $this->log_channel = 'simulator';
        $this->group = $group;
        $this->days = $days;
        $this->log_print = false;
        $this->channels = $channels ?? [];
        $this->programs = [];
        $this->filename = false;
    }

    /**
     * 单纯模拟运行时，生成虚拟频道列表（按日期）
     */
    public static function generateFakeChannels($begin, $days, $group='xkc')
    {
        $day = strtotime($begin);
        $channels = [];
        for($i=0;$i<$days;$i++)
        {
            $channel = new Channel();
            $channel->id = $i;
            $channel->name = $group;
            $channel->air_date = date('Y-m-d', $day);
            $day += 86400;

            $channels[] = $channel;
        }
        return $channels;
    }

    public function setErrorMark($errors, $date)
    {
        if(count($errors)) {
            if(!Storage::disk('data')->exists(XkcGenerator::STALL_FILE))
                Storage::disk('data')->put(XkcGenerator::STALL_FILE, $date);
        }
        else {
            if(Storage::disk('data')->exists(XkcGenerator::STALL_FILE)) {
                
                Notify::fireNotify(
                    $this->group,
                    Notification::TYPE_GENERATE, 
                    "节目单自动生成模版错误已解决", 
                    "处理日期时间: ".date('Y-m-d H:i:s').' 描述: '.Storage::disk('data')->get(XkcGenerator::STALL_FILE),
                    Notification::LEVEL_INFO
                );
                Storage::disk('data')->delete(XkcGenerator::STALL_FILE);
            }
                
        }
    }

    public function getErrorMark()
    {
        if(Storage::disk('data')->exists(XkcGenerator::STALL_FILE))
            return Storage::disk('data')->get(XkcGenerator::STALL_FILE);
        else
            return false;
    }

    public function saveTemplate($templates, $channels)
    {
        if(!$this->saveState) return;
        $temp = compact('templates', 'channels');
        if(count($channels) == 0) return;
        $filename = $this->group.'_'.$channels[0]->air_date.'_'.count($channels).'_template.json';
        Storage::put($filename, json_encode($temp));
        $this->filename = $filename;
    }

    public function saveTemplateHistory($template, $channel)
    {
        if(!$this->saveState) return;
        ChannelGenerator::saveHistory($template, $channel);
    }

    public function handle(\Closure $callback=null)
    {
        //$day = strtotime($start);
        $group = $this->group;
        $errors = [];
        $data = [];
        $lastDate = '';

        $this->plans = new AdvertisePlan($group);
        $this->plans->loadPlans();
        
        $templates = Template::with('records')->where(['group_id'=>$group,'schedule'=>Template::DAILY,'status'=>Template::STATUS_SYNCING])->orderBy('sort', 'asc')->get();
        $this->saveTemplate($templates, $this->channels);
        
        foreach($this->channels as &$channel)
        {
            // setup $air value, for record items expiration check 
            Record::loadExpiration($channel->air_date);
            
            $result = $channel->toArray();
            $result['data'] = [];
            $result['error'] = false;
            //$this->warn("start date:" . $channel->air_date);
            $air = 0;
            $programs = [];
            
            foreach($templates as &$template)
            {
                $this->saveTemplateHistory($template, $channel);

                if($air == 0) $air = strtotime($channel->air_date.' '.$template->start_at);  
                $epglist = []; 
                $duration = 0;
                // This is one single Program
                $program = ChannelGenerator::createChannelProgram($template);

                $program->channel_id = $channel->id;
                $program->start_at = date('Y-m-d H:i:s', $air);
                $program->duration = $duration;
                $program->data = [];
                $program->end_at = date('Y-m-d H:i:s', $air);
                
                $template_items = $template->records;

                $template_item = $this->findAvailableTemplateItem($channel, $template_items);

                $templateresult = $template->toArray();

                $templateresult['error'] = '';
                
                if(!$template_item) {
                    //$this->info("没有找到匹配的模版: {$template->id} {$template->category}");
                    $templateresult['error'] = "没有找到匹配的模版: {$template->id} {$template->category}";
                    
                    $templateresult['program'] = $program->toArray();
                    $templateresult['template'] = [];

                    $result['data'][] = $templateresult;
                    $result['error'] = true;
                    $errors[] = "没有找到匹配的模版: {$template->id} {$template->category}";
                    continue;
                }
                

                //$this->info("template data: ".$template_item->data['episodes'].', '.$template_item->data['unique_no'].', '.$template_item->data['result'] );

                $maxDuration = ChannelGenerator::parseDuration($template->duration) + (int)config('MAX_DURATION_GAP', 600);
                Record::$islast = false;
                $items = $this->findAvailableRecords($template_item, $maxDuration, $air);

                if(count($items)) {
                    foreach($items as $item) {
                        $seconds = ChannelGenerator::parseDuration($item->duration);
                        if($seconds > 0) {
                            
                            $duration += $seconds;
                            
                            $line = ChannelGenerator::createItem($item, $template_item->category, date('H:i:s', $air));
                            
                            $air += $seconds;

                            $line['end_at'] = date('H:i:s', $air);

                            $epglist[] = $callback ? call_user_func($callback, $line) : $line;

                            //$this->info("添加节目: {$template_item->category} {$item->name} {$item->duration}");
                        }
                        else {

                            $templateresult['error'] = "异常3，节目时长为0 {$item->name} {$item->duration} {$item->unique_no} <br/>";
                            $result['error'] = true;
                            $errors[] = "异常3，节目时长为0  {$item->name} {$item->duration} {$item->unique_no} ";
                            
                        }
                    }
                    if(count($epglist) == 0) {
                        //$this->error(" 异常1，没有匹配到任何节目  {$template_item->id} {$template_item->category}");
                        $templateresult['error'] .= "异常1，没有匹配到任何节目  {$template_item->id} {$template_item->category}";
                        $result['error'] = true;
                        $errors[] = "异常1，没有匹配到任何节目  {$template_item->id} {$template_item->category}";
                    }
                    
                }
                else {
                    //$this->error(" 异常2，没有匹配到任何节目  {$template_item->id} {$template_item->category}");
                    $templateresult['error'] = "异常2，没有匹配到任何节目  {$template_item->id} {$template_item->category}";
                    $result['error'] = true;
                    $errors[] = "异常2，没有匹配到任何节目  {$template_item->id} {$template_item->category}";
                }

                $program->duration = $duration;
                $program->data = $epglist;
                $program->end_at = date('Y-m-d H:i:s', $air);

                $advertise = $this->findAvailableAdvertise($channel, $template);

                if($advertise) {
                    $seconds = ChannelGenerator::parseDuration($advertise->duration);
                    if($seconds > 0) {
                            
                        $duration += $seconds;
                        $category = is_array($advertise->category) ? $advertise->category[0]:$advertise->category;
                            
                        $line = ChannelGenerator::createItem($advertise, $category, date('H:i:s', $air));
                            
                        $air += $seconds;

                        $line['end_at'] = date('H:i:s', $air);

                        $epglist[] = $callback ? call_user_func($callback, $line) : $line;

                        $program->duration = $duration;
                        $program->data = $epglist;
                        $program->end_at = date('Y-m-d H:i:s', $air);
                    }
                }
                
                $templateresult['template'] = json_decode(json_encode($template_item), true);
                $templateresult['program'] = $program->toArray();

                if($this->saveState) $template_item->save();
                $result['data'][] = $templateresult;
                $programs[] = $program;
                
            }
            $data[] = $result;
            if($result['error'] && $lastDate == '') $lastDate = $channel->air_date;
            
            $this->programs[$channel->air_date] = $programs;
        }

        $this->setErrorMark($errors, $lastDate);
        $this->errors = $errors;
        $this->templates = $templates;

        return $data;
    }

    public function setSaveTemplateState(bool $state)
    {
        $this->saveState = $state;
    }

    private function findAvailableRecords(&$template, $maxDuration, $air)
    {
        $items = [];
        if($template->type == TemplateRecords::TYPE_RANDOM) {
            $temps = Record::findNextAvaiable($template, $maxDuration, $air);
            if(in_array($temps[0], ['finished', 'empty', 'empty2'])) {
                $d = $template->data;
                $d['episodes'] = null;
                $d['unique_no'] = '';
                $d['name'] = '';
                $d['result'] = '';
                $template->data = $d;

                $temps = Record::findNextAvaiable($template, $maxDuration, $air);
            }
            $d = $template->data;
            foreach($temps as $item) {
                if(!in_array($item, ['finished', 'empty', 'empty2'])) {
                    $items[] = $item;
                    $d['episodes'] = $item->episodes;
                    $d['unique_no'] = $item->unique_no;
                    $d['name'] = $item->name;
                    $d['result'] = '编排中';
                    $template->data = $d;
                }
            }
            
        }
        else if($template->type == TemplateRecords::TYPE_STATIC) {
                
            $temps = Record::findNextAvaiable($template, $maxDuration, $air);
            $items = [];

            $d = $template->data;
            foreach($temps as $idx=>$item) {
                if($item == 'empty') {
                    $d['result'] = '错误:找不到匹配的节目';
                }
                else if($item == 'finished') {
                    if($idx == 0)
                        $d['result'] = '编排完:所有编排规则已完成';
                    else $d['result'] = '编排完';
                }
                else if($item == 'empty2') {
                    $d['result'] = '错误:不是首播日，导致编排无法进行';
                }
                else {
                    $items[] = $item;
                    $d['episodes'] = $item->episodes;
                    $d['unique_no'] = $item->unique_no;
                    $d['name'] = $item->name;
                    $d['result'] = Record::$islast ? '编排完' : '编排中';
                }
                $template->data = $d;
                //$p->save();
            }
        }

        return $items;
    }

    private function findAvailableAdvertise($channel, $template)
    {
        $plan = $this->plans->filterPlan($channel, $template);
        if($plan) {
            $unique_no = $plan->data;
            $item = Record::findUnique($unique_no);
            return $item;
        }
        return false;
    }

    private function findAvailableTemplateItem($channel, &$templateItems)
    {
        $air = strtotime($channel->air_date);
        $dayofweek = date('N', $air);

        foreach($templateItems as &$p)
        {
            if(!in_array($dayofweek, $p->data['dayofweek'])) continue;
            $begin = $p->data['date_from'] ? strtotime($p->data['date_from']) : 0;
            $end = $p->data['date_to'] ? strtotime($p->data['date_to']) : 9999999999;
            if($air < $begin || $air > $end) {
                //$lasterror = "{$p->id} {$p->category} 编排设定时间 {$p->data['date_from']}/{$p->data['date_to']} 已过期";
                continue;
            }

            if($p->data['result'] == '编排完') continue;

            return $p;
        }

        return false;
    }
    
    public function getChannels()
    {
        return $this->channels;
    }

    public function setChannels($channels)
    {
        $this->channels = $channels;
    }

    public function getPrograms($air_date=false)
    {
        if($air_date)
        {
            return array_key_exists($air_date, $this->programs) ? $this->programs[$air_date] : false;
        }

        return $this->programs;
    }
}
