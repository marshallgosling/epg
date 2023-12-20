<?php

namespace App\Tools;

use App\Models\Category;
use App\Models\Channel;
use App\Models\Temp\Template;
use App\Models\ChannelPrograms;
use App\Models\Material;
use App\Models\Notification;
use App\Models\Program;
use App\Models\Record;
use App\Models\TemplatePrograms;
use App\Models\Temp\TemplateRecords;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Tools\Generator\GenerationException;

class ChannelGenerator
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

    public function makeCopyTemplate()
    {
        $group = $this->group;

        DB::table('temp_template')->where('group_id', $group)->delete();
        DB::table('temp_template')->insertUsing(\App\Models\Template::PROPS, 
            DB::table('template')->selectRaw(implode(',', Template::PROPS)
        )->where(['group_id' => $group, 'status'=> Template::STATUS_SYNCING]));
        
        $this->templates = Template::select('id')->where(['group_id' => $group, 'status'=> Template::STATUS_SYNCING])->pluck('id')->toArray();
        DB::table('temp_template_programs')->whereIn('template_id', $this->templates)->delete();
        DB::table('temp_template_programs')->insertUsing(\App\Models\TemplateRecords::PROPS, 
            DB::table('template_programs')->selectRaw(implode(',', TemplateRecords::PROPS)
        )->whereIn('template_id', $this->templates));

    }

    public function saveTemplateState() 
    {
        $list = TemplateRecords::whereIn('template_id', $this->templates)->select('id','data')->pluck('data', 'id')->toArray();

        foreach($list as $id=>$data)
        {
            \App\Models\TemplateRecords::find($id)->update(['data'=>$data]);
        }
    }

    public function cleanTempData()
    {
        DB::table('temp_template_programs')->whereIn('template_id', $this->templates)->delete();
        DB::table('temp_template')->where('group_id', $this->group)->delete();
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

            $schecule += $this->parseDuration($t->duration);

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
            
            $this->addSpecial($programs, $sort);
        }

        $start_end .= ' - '. date('H:i:s', $this->air);

        return $start_end;
        
    }

    public function generateXkc(Channel $channel)
    {
        if(!$channel) {
                
            return ["satus" =>false, "message"=>"Channel is null"];
        }

        $class = $channel->name == 'xkv' ? '\App\Models\Program' : '\App\Models\Record';

        //if($this->daily)
        $this->air = 0;//strtotime($channel->air_date." 06:00:00");
        $schedule_duration = 0;//strtotime($channel->air_date." 06:00:00");
        $schedule_end = 0;
        $start_end = '';
        //$class::loadBlackList();
        Record::loadBumpers();
        

        foreach($this->daily as $t) {    
            // check Date using Weekends Template or not.
            // $t = $this->loadWeekendsTemplate(date('Y-m-d H:i:s', $schecule), $t);
            if($this->air == 0) {
                $this->air = strtotime($channel->air_date.' '.$t->start_at);
                $start_end = $t->start_at;
                $dayofweek = date('N', $this->air);
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
            
            $schedule_duration = self::parseDuration($t->duration);
            $schedule_end = strtotime($channel->air_date.' '.$t->end_at); 
            if($schedule_end < $this->air) $schedule_end += 86400;

            $this->info("开始生成节目单: {$t->name} {$t->start_at}");
            
            $programs = $t->records()->get();

            $this->maxDuration = $schedule_duration + (int)config('MAX_DURATION_GAP', 600);
            $data = $this->addRecordItem($programs, $this->maxDuration, strtotime($channel->air_date), $dayofweek);

            $break_level = 5;
            while(abs($schedule_duration - $this->duration) > (int)config('MAX_GENERATION_GAP', 300))
            {
                $pr = $this->addPRItem();
                if($pr) {
                    $data[] = $pr;
                }
                $break_level --;
                if($break_level < 0) {
                    
                    break;
                }
            }

            $break_level = 3;
            while(abs($schedule_duration - $this->duration) > (int)config('MAX_GENERATION_GAP', 300))
            {
                // 如果当前累加的播出时间和计划播出时间差距大于5分钟，
                // 凑时间，凑节目数
                $res = $this->addBumperItem($schedule_end, $break_level, $class);
                if($res) {
                    $data[] = $res;
                }
                else {
                    // 4次循环后，还是没有找到匹配的节目，则跳出循环
                    $break_level --;
                }

                if($break_level < 0) {
                    $this->warn(" 没有找到合适的Bumper，强制跳出循环.");
                    break;
                }
            }

            $c->duration = $this->duration;
            $c->data = json_encode($data);
            $c->end_at = date('Y-m-d H:i:s', $this->air);
            $c->save();

        }

        if($this->special) {
            $programs = ChannelPrograms::where('channel_id', $channel->id)->orderBy('sort')->get();
            $sort = $t->sort + 1;
            $this->addSpecial($programs, $sort);
        }

        $start_end .= ' - '. date('H:i:s', $this->air);

        return $start_end;

    }

    public function addRecordItem($templates, $maxduration, $air, $dayofweek='')
    {
        $data = [];
        $lasterror = '';
        
        foreach($templates as $p) {
            $item = false;
            $items = [];
            if(!in_array($dayofweek, $p->data['dayofweek'])) continue;
            $begin = $p->data['date_from'] ? strtotime($p->data['date_from']) : 0;
            $end = $p->data['date_to'] ? strtotime($p->data['date_to']) : 999999999999;
            if($air < $begin || $air > $end) {
                $lasterror = "{$p->id} {$p->category} 编排设定时间 {$p->data['date_from']}/{$p->data['date_to']} 已过期";
                continue;
            }
            if($p->type == TemplateRecords::TYPE_RANDOM) {
                $item = Record::findNextAvaiable($p, $maxduration);
                if(!$item) {
                    $d = $p->data;
                    $d['episodes'] = null;
                    $d['unique_no'] = '';
                    $d['name'] = '';
                    $d['result'] = '';
                    $p->data = $d;
 
                    $item = Record::findNextAvaiable($p, $maxduration);
                }

                if(!$item) {
                    //Notify::fireNotify('xkc', Notification::TYPE_GENERATE, '没有找到匹配的节目', "{$p->id} {$p->category} {$p->name} ", 'error');
                    $d = $p->data;
                    $d['result'] = '出错';
                    $p->data = $d;
                    $p->save();
                    throw new GenerationException("随机栏目 {$p->id} {$p->category} 内没有任何节目", Notification::TYPE_GENERATE, "{$p->id} {$p->category} {$p->name} 没有找到匹配的节目");
                }

                $items[] = $item;
            }
            else if($p->type == TemplateRecords::TYPE_STATIC) {
                $data = $p->data;
                $ep = 1;
                if(array_key_exists('ep', $data)) {
                    $ep = (int)$data['ep'];
                }
                for($i=0;$i<$ep;$i++) {
                    $item = Record::findNextAvaiable($p, $maxduration);
                    if($item) $items[] = $item;
                }

                if(count($items) == 0) continue;
            }
            
            if(count($items)) {
                foreach($items as $item) {
                    $seconds = self::parseDuration($item->duration);
                    if($seconds > 0) {
                        
                        $this->duration += $seconds;
                        
                        $line = ChannelGenerator::createItem($item, $p->category, date('H:i:s', $this->air));
                        
                        $this->air += $seconds;

                        $line['end_at'] = date('H:i:s', $this->air);

                        $data[] = $line;
                            
                        $this->info("添加节目: {$p->category} {$item->name} {$item->duration}");

                        // Only add once;
                        break;
                    }
                    else {

                        $this->warn(" {$item->name} 的时长为 0 （{$item->duration}）, 因此忽略.");
                        //throw new GenerationException("{$item->name} 的时长为 0 （{$item->duration}）", Notification::TYPE_GENERATE);
                    }
                }
            }
            else
            {
                $this->warn("栏目 {$p->id} {$p->category} 内没有任何节目");
                throw new GenerationException("栏目 {$p->id} {$p->category} 内没有任何节目", Notification::TYPE_GENERATE);
            }
        }

        if(count($data) == 0) {
            $this->error("栏目 {$p->id} {$p->category} 内没有匹配到任何节目");
            $d = $p->data;
            $d['result'] = '出错';
            $p->data = $d;
            $p->save();
            throw new GenerationException("栏目 {$p->id} {$p->category} 内没有匹配到任何节目", Notification::TYPE_GENERATE, $lasterror);
        }
        return $data;
    }


    public function addProgramItem($programs, $class)
    {
        $data = [];
        
        foreach($programs as $p) {
            $item = false;

            if($p->type == TemplatePrograms::TYPE_PROGRAM) {
                if($class == '\App\Models\Record') {
                    if($p->category == 'movie')
                        $item = $class::findRandom($p->category);
                    else {
                        if($p->name) {
                            $item = $class::findNextEpisode($p->name, $p->data);       
                        }
                        
                        if(!$item) {
                            $item = $class::findRandomEpisode($p->category);
                        }  

                        $p->name = $item->episodes;
                        $p->data = $item->unique_no;
                        $p->save();
                    }
                }
                else {
                    $item = $class::findRandom($p->category);
                }
                
            }
            else {
                $item = $class::findUnique($p->data);

                if(!$item) {
                    $item = Material::findUnique($p->data);
                }
            }
            
            if($item) {
                $seconds = self::parseDuration($item->duration);
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

    public function addPRItem($category='XK PR')
    {
        $item = Record::findPR($category);

        $this->info("find bumper: {$item->name} {$item->duration}");
        $seconds = ChannelGenerator::parseDuration($item->duration);

        //$air = $this->air + $seconds;

        $this->duration += $seconds;

        $line = ChannelGenerator::createItem($item, $category, date('H:i:s', $this->air));
                    
        $this->air += $seconds;
        

        $line['end_at'] = date('H:i:s', $this->air);

        $this->info("添加PR 节目: {$category} {$item->name} {$item->duration}");

        return $line;
    }

    public function addBumperItem($schedule_end, $break_level, $class, $category='m1')
    {
        $item = $class::findBumper($break_level);

        $this->info("find bumper: {$item->name} {$item->duration}");
        $seconds = ChannelGenerator::parseDuration($item->duration);

        $air = $this->air + $seconds;

        $this->info("air time: ".date('Y/m/d H:i:s', $air). " {$air}, schedule: ".date('Y/m/d H:i:s', $schedule_end));
        if($air > ($schedule_end + (int)config('GENERATE_GAP', 300))) return false;

        $this->duration += $seconds;
                    
        $line = ChannelGenerator::createItem($item, $category, date('H:i:s', $this->air));
                    
        $this->air += $seconds;

        $line['end_at'] = date('H:i:s', $this->air);

        $this->info("添加Bumper 节目: {$category} {$item->name} {$item->duration}");

        return $line;
    }

    public function addSpecial($programs, $sort)
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

                $p->schedule_start_at = self::scheduleTime($p->schedule_start_at, $t->duration, ($idx+1));
                $p->schedule_end_at = self::scheduleTime($p->schedule_end_at, $t->duration, ($idx+1));

                $p->save();
                $sort ++;
            }
            $this->info("复制节目 {$t->name} {$t->start_at} {$t->end_at}");
        }

        return date('H:i:s', $this->air);
    }

    public static function scheduleTime($origin, $duration, $multi=1)
    {
        $time = strtotime('2020/01/01 '.$origin);
        $seconds = self::parseDuration($duration);
        $time += $seconds * $multi;
        return date("H:i:s", $time);
    }


    /**
     * create an Item obj
     * 
     * @param mixed $program
     * 
     * @return array
     */
    public static function createItem($program, $category='', $air='')
    {
        return [
            "unique_no" => $program->unique_no,
            "name" => $program->name,
            "duration" => $program->duration,
            "category" => $category,
            "start_at" => $air,
            "artist" => $program instanceof Program ? $program->artist : $program->episodes,
            "end_at" => ''
        ];
    }

    public static function createExcelItem($item, $name, $no, $air)
    {
        return [
            $no, $name, $item->name, $item->unique_no, substr($air, 2),
            $item->start_at.':00', $item->end_at.':00', $item->duration, '00:00:00:00', ''
        ];
    }

    public static function createXmlItem($item)
    {
        return [
            "unique_no" => $item->unique_no,
            "name" => $item->name,
            "duration" => $item->duration,
            "category" => $item->category,
            "start_at" => $item->start_at,
            "artist" => $item->artist,
            "end_at" => $item->end_at
        ];
    }

    /**
     * Parse duration format 00:00:00:00 to seconds (int).
     * 
     * @param string $str
     * @return int  
     */
    public static function parseDuration($str)
    {
        $duration = explode(':', $str);
        
        $seconds = count($duration )>= 3 ? (int)$duration[0]*3600 + (int)$duration[1]*60 + (int)$duration[2] : 0;

        return $seconds;
    }

    public static function parseFrames($frames)
    {
        $seconds = floor($frames / config("FRAMES", 25));

        $hour = floor($seconds / 3600);
        $minute = floor(($seconds % 3600)/60);
        $second = $seconds % 60;

        return self::format($hour).':'.self::format($minute).':'.self::format($second).':'.self::format($frames % config("FRAMES", 25));
    }

    public static function formatDuration($seconds)
    {
        $hour = floor($seconds / 3600);
        $minute = floor(($seconds % 3600)/60);
        $second = $seconds % 60;

        return self::format($hour).':'.self::format($minute).':'.self::format($second);
    }

    public static function format($num)
    {
        return $num>9?$num:'0'.$num;
    }

    public static function caculateDuration($data, $start=0)
    {
        foreach($data as &$item)
        {
            $seconds = self::parseDuration($item->duration);

            $item->start_at = date('H:i:s', $start);
            $start += $seconds;
            $item->end_at = date('H:i:s', $start);

        }

        return $data;
    }

    public static function writeTextMark($group, $date)
    {
        try {
            
            $d = Storage::exists($group.'.txt') ? strtotime(Storage::get($group.'.txt')) : 0;
            $d2 = strtotime($date);
            if($d2 > $d)
                Storage::put($group.'.txt', $date);
        }
        catch(\Exception $e)
        {

        }
    }

}