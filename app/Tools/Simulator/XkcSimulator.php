<?php
namespace App\Tools\Simulator;

use App\Models\Channel;
use App\Models\Template;
use App\Models\ChannelPrograms;
use App\Models\Notification;
use App\Models\Record;
use App\Models\TemplateRecords;
use App\Tools\ChannelGenerator;
use App\Tools\Generator\XkcGenerator;
use Illuminate\Support\Facades\DB;

use App\Tools\LoggerTrait;
use App\Tools\Notify;
use Illuminate\Support\Facades\Storage;

class XkcSimulator
{
    use LoggerTrait;

    private $channel;
    private $group;
    /**
     * 按24小时累加的播出时间，格式为 timestamp ，输出为 H:i:s
     */
    private $air;
    public $errors;
    /**
     * 统计一档节目的时长，更换新节目时重新计算
     */
    private $duration;
    
    public function __construct($group)
    {
        $this->log_channel = 'simulator';
        $this->group = $group;
        $this->log_print = false;
    }

    public function setErrorMark($errors)
    {
        if(count($errors)) {
            Storage::disk('data')->put(XkcGenerator::STALL_FILE, $errors[0]);
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

    public function handle($start, $days, \Closure $callback=null)
    {
        $day = strtotime($start);
        $group = $this->group;
        $errors = [];
        $data = [];

        $templates = Template::with('records')->where(['group_id'=>$group,'schedule'=>Template::DAILY,'status'=>Template::STATUS_SYNCING])->orderBy('sort', 'asc')->get();
        
        for($i=0;$i<$days;$i++)
        {
            $channel = new Channel();
            $channel->id = $i;
            $channel->name = $group;
            $channel->air_date = date('Y-m-d', $day);
            $day += 86400;
            $result = $channel->toArray();
            $result['data'] = [];
            $result['error'] = false;
            //$this->warn("start date:" . $channel->air_date);
            $air = 0;
            $duration = 0;
            $epglist = [];
            
            foreach($templates as &$template)
            {
                if($air == 0) $air = strtotime($channel->air_date.' '.$template->start_at);  
                $epglist = []; 
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

                $templateresult['error'] = false;
                
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

                $maxDuration = ChannelGenerator::parseDuration($template->duration); + (int)config('MAX_DURATION_GAP', 600);
                $items = $this->findAvailableRecords($template_item, $maxDuration);

                if(count($items)) {
                    foreach($items as $item) {
                        $seconds = ChannelGenerator::parseDuration($item->duration);
                        if($seconds > 0) {
                            
                            $duration += $seconds;
                            
                            $line = ChannelGenerator::createItem($item, $template_item->category, date('H:i:s', $air));
                            
                            $air += $seconds;

                            $line['end_at'] = date('H:i:s', $air);

                            $epglist[] = $callback ? call_user_func($callback, $line) : $line;
                            //$templateresult['epglist'][] = $line;
                            //$this->info("添加节目: {$template_item->category} {$item->name} {$item->duration}");
                        }
                        else {

                            //$this->warn(" {$item->name} 的时长为 0 （{$item->duration}）, 因此忽略.");
                            //throw new GenerationException("{$item->name} 的时长为 0 （{$item->duration}）", Notification::TYPE_GENERATE);
                        }
                    }
                    if(count($epglist) == 0) {
                        //$this->error(" 异常1，没有匹配到任何节目  {$template_item->id} {$template_item->category}");
                        $templateresult['error'] = "异常1，没有匹配到任何节目  {$template_item->id} {$template_item->category}";
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
                
                $templateresult['template'] = json_decode(json_encode($template_item), true);
                $templateresult['program'] = $program->toArray();

                $result['data'][] = $templateresult;

            }
            $data[] = $result;
        }

        $this->setErrorMark($errors);
        $this->errors = $errors;
        return $data;
    }

    private function findAvailableRecords(TemplateRecords &$template, $maxDuration)
    {
        $items = [];
        if($template->type == TemplateRecords::TYPE_RANDOM) {
            $temps = Record::findNextAvaiable($template, $maxDuration);
            if(in_array($temps[0], ['finished', 'empty'])) {
                $d = $template->data;
                $d['episodes'] = null;
                $d['unique_no'] = '';
                $d['name'] = '';
                $d['result'] = '';
                $template->data = $d;

                $temps = Record::findNextAvaiable($template, $maxDuration);
            }
            $d = $template->data;
            foreach($temps as $item) {
                if(!in_array($item, ['finished', 'empty'])) {
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
                
            $temps = Record::findNextAvaiable($template, $maxDuration);
            $items = [];

            $d = $template->data;
            foreach($temps as $item) {
                if($item == 'empty') {
                    $d['result'] = '错误';
                }
                else if($item == 'finished') {
                    $d['result'] = '编排完';
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

    private function findAvailableTemplateItem($channel, &$templateItems)
    {
        $air = strtotime($channel->air_date);
        $dayofweek = date('N', $air);

        foreach($templateItems as &$p)
        {
            if(!in_array($dayofweek, $p->data['dayofweek'])) continue;
            $begin = $p->data['date_from'] ? strtotime($p->data['date_from']) : 0;
            $end = $p->data['date_to'] ? strtotime($p->data['date_to']) : 999999999999;
            if($air < $begin || $air > $end) {
                $lasterror = "{$p->id} {$p->category} 编排设定时间 {$p->data['date_from']}/{$p->data['date_to']} 已过期";
                continue;
            }

            if($p->data['result'] == '编排完') continue;

            return $p;
        }

        return false;
    }
    
}
