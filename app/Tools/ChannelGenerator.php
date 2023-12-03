<?php

namespace App\Tools;

use App\Models\Category;
use App\Models\Channel;
use App\Models\Template;
use App\Models\ChannelPrograms;
use App\Models\Material;
use App\Models\Program;
use App\Models\TemplatePrograms;
use Carbon\Carbon;

class ChannelGenerator
{
    use LoggerTrait;

    private $channel;
    private $templates;
    private $daily;
    private $weekends;
    private $special;
    
    public function __construct()
    {
        $this->log_channel = 'channel';
    }

    /**
     * Load all templates
     * 
     * @param Channel $channel
     * @param string $group
     * 
     */
    public function loadTemplate($channel, $group='default')
    {
        $this->channel = $channel;
        $this->daily = Template::where(['group_id'=>$group,'schedule'=>Template::DAILY,'status'=>Template::STATUS_SYNCING])->with('programs')->orderBy('sort', 'asc')->get();
        $this->weekends = Template::where(['group_id'=>$group,'schedule'=>Template::WEEKENDS,'status'=>Template::STATUS_SYNCING])->with('programs')->orderBy('sort', 'asc')->get();
        $this->special = Template::where(['group_id'=>$group,'schedule'=>Template::SPECIAL,'status'=>Template::STATUS_SYNCING])->with('programs')->orderBy('sort', 'asc')->get();
        
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

        $air = strtotime($channel->air_date." 06:00:00");
        $schecule = strtotime($channel->air_date." 06:00:00");
        Program::loadBlackList();

        foreach($this->daily as $t) {
            
            // check Date using Weekends Template or not.
            $t = $this->loadWeekendsTemplate(date('Y-m-d H:i:s', $schecule), $t);

            $c = new ChannelPrograms();
            $c->name = $t->name;
            $c->schedule_start_at = $t->start_at;
            $c->schedule_end_at = $t->end_at;
            $c->channel_id = $channel->id;
            $c->start_at = date('Y-m-d H:i:s', $air);
            $c->duration = 0;
            $c->version = '1';
            $c->sort = $t->sort;

            $schecule += $this->parseDuration($t->duration);

            $this->info("开始生成节目单: {$t->name} {$t->start_at}");
            
            $data = [];
            $programs = $t->programs()->get();
            foreach($programs as $p) {
                $item = false;

                if($p->type == TemplatePrograms::TYPE_PROGRAM) {
                    $item = Program::findRandom($p->category);
                    
                }
                else {
                    $item = Program::findUnique($p->data);

                    if(!$item) {
                        $item = Material::findUnique($p->data);
                    }
                }
                
                if($item) {
                    $seconds = self::parseDuration($item->duration);
                    if($seconds > 0) {
                        
                        $c->duration += $seconds;
                        
                        $line = ChannelGenerator::createItem($item, $p->category, date('H:i:s', $air));
                        
                        $air += $seconds;

                        $line['end_at'] = date('H:i:s', $air);

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
            $c->data = json_encode($data);
            $c->end_at = date('Y-m-d H:i:s', $air);
            $c->save();

        }
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
            "artist" => $program instanceof Program ? $program->artist : '',
            "end_at" => ''
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

}