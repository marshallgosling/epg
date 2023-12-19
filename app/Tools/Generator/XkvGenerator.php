<?php
namespace App\Tools\Generator;

use App\Models\Channel;
use App\Models\Temp\Template;
use App\Models\ChannelPrograms;
use App\Models\Material;
use App\Models\TemplatePrograms;
use App\Tools\ChannelGenerator;
use Carbon\Carbon;
use App\Tools\LoggerTrait;

class XkvGenerator implements IGenerator
{

    use LoggerTrait;

    private $channel;
    private $templates;
    private $daily;
    private $weekends;
    private $special;
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
    
    public function __construct($group)
    {
        $this->log_channel = 'channel';
        $this->group = $group;
    }

    /**
     * Load all templates
     * 
     * @param Channel $channel
     * @param string $group
     * 
     */
    public function loadTemplate()
    {
        $group = $this->group;
        $this->daily = Template::where(['group_id'=>$group,'schedule'=>Template::DAILY,'status'=>Template::STATUS_SYNCING])->orderBy('sort', 'asc')->get();
        $this->weekends = Template::where(['group_id'=>$group,'schedule'=>Template::WEEKENDS,'status'=>Template::STATUS_SYNCING])->orderBy('sort', 'asc')->get();
        $this->special = Template::where(['group_id'=>$group,'schedule'=>Template::SPECIAL,'status'=>Template::STATUS_SYNCING])->orderBy('sort', 'asc')->get();
    }

    private function loadWeekendsTemplate($date, $daily)
    {
        $air = Carbon::parse($date);

        if($air->dayOfWeekIso > 5) {
            if(!$this->weekends) return $daily;

            foreach($this->weekends as $weekend)
            {
                if($weekend->start_at == $daily->start_at) {
                    return $weekend;
                }
            }
        }

        return $daily;
    }

    public function generate(Channel $channel)
    {
        
        if(!$channel) {
            
            return ["satus" =>false, "message"=>"Channel is null"];
        }

        $class = $channel->name == 'xkv' ? '\App\Models\Program' : '\App\Models\Record';

        //if($this->daily)
        $this->air = 0;//strtotime($channel->air_date." 06:00:00");
        $schecule = 0;//strtotime($channel->air_date." 06:00:00");
        $class::loadBlackList();
        $start_end = '';
        $sort=0;
        foreach($this->daily as $t) {    
            // check Date using Weekends Template or not.
            $t = $this->loadWeekendsTemplate(date('Y-m-d H:i:s', $schecule), $t);
            if($this->air == 0) {
                $this->air = strtotime($channel->air_date.' '.$t->start_at); 
                $start_end = $t->start_at;
            }

            $this->duration = 0;

            $c = new ChannelPrograms();
            $c->name = $t->name;
            $c->schedule_start_at = $t->start_at;
            $c->schedule_end_at = $t->end_at;
            $c->channel_id = $channel->id;
            $c->start_at = date('Y-m-d H:i:s', $this->air);
            $c->version = '1';
            $c->sort = $t->sort;

            $schecule += ChannelGenerator::parseDuration($t->duration);

            $this->info("开始生成节目单: {$t->name} {$t->start_at}");
            
            $programs = $t->programs()->get();

            $data = $this->addProgramItem($programs, $class);

            // $bumper = $this->addBumperItem();
            
            $c->duration = $this->duration;
            $c->data = json_encode($data);
            $c->end_at = date('Y-m-d H:i:s', $this->air);
            $c->save();
            $sort = $t->sort + 1;
        }

        if($this->special) {
            $programs = ChannelPrograms::where('channel_id', $channel->id)->orderBy('sort')->get();
            
            $this->addSpecialPrograms($programs, $sort);
        }

        $start_end .= ' - '. date('H:i:s', $this->air);

        return $start_end;
        
    }

    public function addPRItem($category='XK PR')
    {
        
    }

    public function addBumperItem($schedule_end, $break_level, $class, $category='m1')
    {
        
    }

    public function addSpecialPrograms($programs, $sort)
    {
        
        foreach($this->special as $idx=>$t) {
            foreach($programs as $program)
            {
                $p = $program->replicate();

                $p->name .= ' (副本)';
                $p->data = json_encode(['replicate'=>$program->id]);
                $p->start_at = date('Y-m-d H:i:s', $this->air);
                $this->air += $p->duration;
                $p->end_at = date('Y-m-d H:i:s', $this->air);                   
                $p->sort = $sort;

                $p->schedule_start_at = ChannelGenerator::scheduleTime($p->schedule_start_at, $t->duration, ($idx+1));
                $p->schedule_end_at = ChannelGenerator::scheduleTime($p->schedule_end_at, $t->duration, ($idx+1));

                $p->save();
                $sort ++;
            }
            $this->info("复制节目 {$t->name} {$t->start_at} {$t->end_at}");
        }

        return date('H:i:s', $this->air);
    }

    public function addProgramItem($programs, $class)
    {
        $data = [];
        
        foreach($programs as $p) {
            $item = false;

            if($p->type == TemplatePrograms::TYPE_PROGRAM) { 
                $item = $class::findRandom($p->category);
            }
            else {
                $item = $class::findUnique($p->data);

                if(!$item) {
                    $item = Material::findUnique($p->data);
                }
            }
            
            if($item) {
                $seconds = ChannelGenerator::parseDuration($item->duration);
                if($seconds > 0) {
                    
                    $this->duration += $seconds;
                    
                    $line = ChannelGenerator::createItem($item, $p->category, date('H:i:s', $this->air));
                    
                    $this->air += $seconds;

                    $line['end_at'] = date('H:i:s', $this->air);

                    $data[] = $line;
                        
                    $this->info("添加节目: {$p->category} {$item->name} {$item->duration}");
                }
                else {

                    $this->warn(" {$item->name} 的时长为 0 （{$item->duration}）, 因此忽略.");

                }
            }
            else
            {
                $this->warn("栏目 {$p->category} 内没有任何节目");
            }
        }

        return $data;

    }

    public function addRecordItem($templates, $maxduration, $air, $dayofweek = '')
    {
        
    }

}