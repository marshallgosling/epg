<?php

namespace App\Tools\Generator;

use App\Events\Channel\CalculationEvent;
use App\Models\Channel;
use App\Models\Template;
use App\Models\ChannelPrograms;
use App\Models\EpgJob;
use App\Models\Notification;
use App\Models\Record2 as Record;
use App\Models\Temp\TemplateRecords;
use App\Tools\ChannelGenerator;
use Illuminate\Support\Facades\DB;

use App\Tools\LoggerTrait;
use App\Tools\Notify;
use App\Tools\Simulator\XkiSimulator;
use Illuminate\Support\Facades\Storage;

class XkiGenerator
{
    use LoggerTrait;

    public const STALL_FILE = "xki_stall.txt";

    private $channels;
    private $templates;
    private $group;

    private $maxDuration = 0;

    public $errors = [];
    
    public function __construct($group='xki')
    {
        $this->log_channel = 'channel';
        $this->group = $group;
        $days = (int)config('SIMULATOR_DAYS', 14);
        //$this->channels = Channel::where(['status'=>Channel::STATUS_WAITING,'name'=>$this->group])->orderBy('air_date')->limit($days)->get();
        
    }

    public function test()
    {
        $days = (int)config('SIMULATOR_DAYS', 14);
        $simulator = new XkiSimulator($this->group, $days, $this->channels);
        //$simulator->saveTemplateState();
        $simulator->handle();

        $error = $simulator->getErrorMark();

        return $error;
    }

    public function reset()
    {
        foreach($this->channels as $channel)
        {
            $channel->status = Channel::STATUS_EMPTY;
            $channel->save();
        }
    }

    private function saveJob($file, $channels)
    {
        if(!$file) return;
        if(count($channels)) $name = $channels[count($channels)-1]->air_date;
        else $name = 'unknow';
        $job = new EpgJob;
        $job->name = $name;
        $job->file = $file;
        $job->group_id = 'xki';
        $job->save();
        //Storage::put($file, json_encode($data));

    }

    public function generate($channels)
    {
        //ChannelGenerator::makeCopyTemplate($this->group);
        Record::cleanCache();
        Record::loadBumpers(config('XKI_BUMPERS_TAG', 'XK FILLER'));

        $days = count($channels);
        //$channels = $this->channels;
        if(!$channels) return false;
        
        $simulator = new XkiSimulator($this->group, $days, $channels);
        $simulator->setSaveTemplateState(true);
        $simulator->handle();

        $error = $simulator->getErrorMark();

        if($error) {
            // Notify error
            return false;
        }

        $this->saveJob($simulator->filename, $channels);

        $special = Template::where(['group_id'=>$this->group,'schedule'=>Template::SPECIAL,'status'=>Template::STATUS_SYNCING])->orderBy('sort', 'asc')->get();
        
        $channels = $simulator->getChannels();
        
        foreach($channels as $channel)
        {
            $channel->status = Channel::STATUS_RUNNING;
            $channel->save();

            $programs = $simulator->getPrograms($channel->air_date);
            $air = $sort = 0; $start_end = '';
            foreach($programs as &$program)
            {
                if($air == 0) {
                    $air = strtotime($program->start_at);
                    $start_end = date('H:i:s', $air);
                }
                else {
                    $program->start_at = date('Y-m-d H:i:s', $air);
                }
                $this->info("program: {$program->start_at} {$program->end_at} {$program->name}");
                $data = $program->data;
                foreach($data as &$p)
                {
                    $p['start_at'] = date('H:i:s', $air);
                    $air += ChannelGenerator::parseDuration($p['duration']);
                    $p['end_at'] = date('H:i:s', $air);
                }
                $scheduledDuration = $this->calculationScheduleDuration($channel->air_date, $program);

                $duration = $program->duration;
                $break_level = 5;
                
                while(abs($scheduledDuration - $duration) > (int)config('MAX_GENERATION_GAP', 300))
                {
                    if($duration > $scheduledDuration) break;
                    $pr = $this->addPRItem($air, config('XKI_PR_TAG', 'XK PR'));
                    if(is_array($pr)) {
                        $data[] = $pr['line'];
                        $duration += $pr['seconds'];
                        $air += $pr['seconds'];
                        $this->info("add PR: ".json_encode($pr, JSON_UNESCAPED_UNICODE));
                    }
                    $break_level --;
                    if($break_level < 0) {
                        
                        break;
                    }
                }

                
                $break_level = 3;
                $schedule_end = strtotime($channel->air_date.' '.$program->schedule_start_at) + $scheduledDuration;
                while(abs($scheduledDuration - $duration) > (int)config('MAX_GENERATION_GAP', 300))
                {
                    if($duration > $scheduledDuration) break;
                    // 如果当前累加的播出时间和计划播出时间差距大于5分钟，
                    // 凑时间，凑节目数
                    $res = $this->addBumperItem($schedule_end, $break_level, $air);
                    if(is_array($res)) {
                        $data[] = $res['line'];
                        $duration += $res['seconds'];
                        $air += $res['seconds'];
                        $this->info("add Bumper: ".json_encode($res, JSON_UNESCAPED_UNICODE));
                    }
                    else {
                        // 4次循环后，还是没有找到匹配的节目，则跳出循环
                        $break_level --;
                    }

                    if($break_level < 0) {
                        //$this->warn(" 没有找到合适的Bumper，强制跳出循环.");
                        break;
                    }
                }

                $program->duration = $duration;
                $program->data = json_encode($data);
                $program->end_at = date('Y-m-d H:i:s', $air);
                $program->save();
                $sort = $program->sort + 1;
            }
            $this->checkDuration($channel, $program, $air);
            $this->addSpecialPrograms($special, $air, $programs, $sort);

            //CalculationEvent::dispatch($channel->id);
            $channel->start_end = $start_end .' - '. date('H:i:s', $air);
            $channel->status = Channel::STATUS_READY;
            $channel->comment = '';//ChannelGenerator::checkAbnormalTimespan($air);
            $channel->lock_status = Channel::LOCK_ENABLE;
            $channel->save();

            // \App\Jobs\StatisticJob::dispatch($channel->id);
            // \App\Jobs\EpgJob::dispatch($channel->id);
            \App\Jobs\AuditEpgJob::dispatch($channel->id, 'Init')->onQueue('audit');

            Notify::fireNotify(
                $channel->name,
                Notification::TYPE_GENERATE, 
                "生成节目编单 {$channel->name}_{$channel->air_date} 数据成功. ", 
                "频道节目时间 $start_end"
            );

            ChannelGenerator::writeTextMark($channel->name, $channel->air_date);
        }

        
            
        return true;

    }

    public function checkDuration($channel, &$program, &$air)
    {
        $start = strtotime($channel->air_date.' '.$program->schedule_end_at) + 86400;
        $end = $air;

        if($start <= $end) return $air;

        $seconds = $start - $end;

        if($seconds > 1800)
        {
            return $air;
        }

        $propose = $seconds;

        if($propose < 60) $propose = 60;

        $data = json_decode($program->data);

        $break_level = 2;

        $data = json_decode($program->data, true);
        //$logs = [];

        while($propose > 0)
        {
            // 如果当前累加的播出时间和计划播出时间差距大于5分钟，
            // 凑时间，凑节目数
            $this->info("add propose: $propose, level: $break_level");
            $res = $this->addBumperItem2($break_level, $propose, $air);
            if(is_array($res)) {
                $data[] = $res['line'];
                $propose -= $res['seconds'];
                $air += $res['seconds'];
                $this->info("propose: $propose, ".json_encode($res, JSON_UNESCAPED_UNICODE));
            }
            else {
                // 4次循环后，还是没有找到匹配的节目，则跳出循环
                $break_level --;
            }

            if($break_level < 0) {
                break;
            }
        }
        $program->data = json_encode($data);
        $program->save();

        return compact('air', 'program');
    
    }

    public function addPRItem($air, $category='XK PR')
    {
        $item = Record::findPR($category);

        // $this->info("find bumper: {$item->name} {$item->duration}");
        $seconds = ChannelGenerator::parseDuration($item->duration);
        
        $line = ChannelGenerator::createItem($item, $category, date('H:i:s', $air));
                    
        $air += $seconds;
        
        $line['end_at'] = date('H:i:s', $air);

        //$this->info("添加PR 节目: {$category} {$item->name} {$item->duration}");

        return compact('line', 'seconds');
    }

    public function addBumperItem($schedule_end, $break_level, $air)
    {
        $item = Record::findBumper($break_level);
        if(!$item) return false;
        //$this->info("find bumper: {$item->name} {$item->duration}");
        $seconds = ChannelGenerator::parseDuration($item->duration);
        $category = $item->category;
        $temp_air = $air + $seconds;
        if(is_array($category)) $category = array_pop($category);
        //$this->info("air time: ".date('Y/m/d H:i:s', $air). " {$air}, schedule: ".date('Y/m/d H:i:s', $schedule_end));
        if($temp_air > ($schedule_end + (int)config('GENERATE_GAP', 300))) return false;
                    
        $line = ChannelGenerator::createItem($item, $category, date('H:i:s', $air));
                    
        $air += $seconds;

        $line['end_at'] = date('H:i:s', $air);

        return compact('line', 'seconds');
    }

    public function addBumperItem2($break_level, $propose, $air)
    {
        $item = Record::findBumper($break_level);

        if(!$item) return false;
        //$this->info("find bumper: {$item->name} {$item->duration}");
        $seconds = ChannelGenerator::parseDuration($item->duration);
        if($seconds > (2*$propose)) return false;
        
        $category = $item->category;
        if(is_array($category)) $category = array_pop($category);
        //$this->info("air time: ".date('Y/m/d H:i:s', $air). " {$air}, schedule: ".date('Y/m/d H:i:s', $schedule_end));
                   
        $line = ChannelGenerator::createItem($item, $category, date('H:i:s', $air));
        $air += $seconds;
        $line['end_at'] = date('H:i:s', $air);

        return compact('line', 'seconds');
    }

    public function addSpecialPrograms($special, &$air, $programs, $sort)
    {
        
        foreach($special as $idx=>$t) {
            foreach($programs as $program)
            {
                $p = $program->replicate();

                $p->name .= ' (副本)';
                $p->data = json_encode(['replicate'=>$program->id]);
                $p->start_at = date('Y-m-d H:i:s', $air);
                $air += $p->duration;
                $p->end_at = date('Y-m-d H:i:s', $air);                   
                $p->sort = $sort;

                $p->schedule_start_at = ChannelGenerator::scheduleTime($p->schedule_start_at, $t->duration, ($idx+1));
                $p->schedule_end_at = ChannelGenerator::scheduleTime($p->schedule_end_at, $t->duration, ($idx+1));

                $p->save();
                $sort ++;
            }
            $this->info("复制节目 {$t->name} {$t->start_at} {$t->end_at}");
        }

        return date('H:i:s', $air);
    }

    private function calculationScheduleDuration($air_date, $program)
    {
        $schedule_begin = strtotime($air_date.' '.$program->schedule_start_at); 
        $schedule_end = strtotime($air_date.' '.$program->schedule_end_at); 
        if($schedule_end < $schedule_begin) $schedule_end += 86400;

        return $schedule_end - $schedule_begin;
    }
}