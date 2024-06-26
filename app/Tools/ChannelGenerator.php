<?php

namespace App\Tools;

use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Material;
use App\Models\Program;
use App\Models\Temp\Template;
use App\Models\Temp\TemplateRecords;
use App\Tools\Generator\XkvGenerator;
use App\Tools\Generator\XkcGenerator;
use App\Tools\Generator\XkiGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChannelGenerator
{
 
    public static function getGenerator($group)
    {
        if($group == 'xkv') return new XkvGenerator($group);
        if($group == 'xkc') return new XkcGenerator($group);
        if($group == 'xki') return new XkiGenerator($group);
    }

    public static function makeCopyTemplate($group)
    {
        
        self::cleanTempData($group);

        DB::table('temp_template')->insertUsing(\App\Models\Template::PROPS, 
            DB::table('template')->selectRaw(implode(',', Template::PROPS)
        )->where(['group_id' => $group, 'status'=> Template::STATUS_SYNCING]));
        
        $templates = Template::select('id')->where(['group_id' => $group, 'status'=> Template::STATUS_SYNCING])->pluck('id')->toArray();
        DB::table('temp_template_programs')->whereIn('template_id', $templates)->delete();
        DB::table('temp_template_programs')->insertUsing(\App\Models\TemplateRecords::PROPS, 
            DB::table('template_programs')->selectRaw(implode(',', TemplateRecords::PROPS)
        )->whereIn('template_id', $templates));

        return $templates;
    }

    public static function saveHistory($template, $channel)
    {
        $temp = $template->replicate()->toArray();

        $temp['group_id'] = $channel->id;
        $temp['created_at'] = $channel->air_date;
        $temp['updated_at'] = date('Y-m-d H:i:s');
        $t = new Template($temp);
        $t->save();
        $records = $template->records;

        foreach($records as $record)
        {
            $r = $record->replicate()->toArray();
            $r['template_id'] = $t->id;
            TemplateRecords::create($r);
        }
    }

    public static function saveTemplateState($templates) 
    {
        $list = TemplateRecords::whereIn('template_id', $templates)->select('id','data')->pluck('data', 'id')->toArray();

        foreach($list as $id=>$data)
        {
            \App\Models\TemplateRecords::find($id)->update(['data'=>$data]);
        }
    }

    public static function cleanTempData($group)
    {
        DB::table('temp_template_programs')->delete();
        DB::table('temp_template')->where('group_id', $group)->delete();
    }


    public static function scheduleTime($origin, $duration, $multi=1)
    {
        $time = strtotime('2020/01/01 '.$origin);
        $seconds = self::parseDuration($duration);
        $time += $seconds * $multi;
        return date("H:i:s", $time);
    }


    /**
     * Create a ChannelProgram Model
     * with Template data
     * 
     * @param Template $t
     * @return ChannelProgram $c
     */
    public static function createChannelProgram($t)
    {
        $c = new ChannelPrograms();
        $c->name = $t->name;
        $c->schedule_start_at = $t->start_at;
        $c->schedule_end_at = $t->end_at;
        $c->channel_id = 0;
        //$c->start_at = date('Y-m-d H:i:s', $this->air);
        $c->version = '1';
        $c->sort = $t->sort;

        return $c;
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

    public static function checkMaterials($programs)
    {
        $unique_no = [];
        foreach($programs as $pro)
        {
            $items = json_decode($pro->data);
            if(array_key_exists('replicate', $items))
            {
                continue;
            }
            else {
                
                foreach($items as $item) {
                    if(!in_array($item->unique_no, $unique_no))
                        $unique_no[] = $item->unique_no;
                }
            }
        }

        return DB::table('material')->whereIn('unique_no', $unique_no)
                            ->where('status', '<>', Material::STATUS_READY)->select(['name','unique_no'])
                            ->pluck('unique_no')->toArray();
    }

    public static function getLatestAirDate($group)
    {
        $channel = Channel::where(['status'=>Channel::STATUS_READY,'name'=>$group])->orderBy('air_date','desc')->first();
        if($channel) {
            $c = strtotime($channel->air_date) + 86400;
            return $c;
        }

        return false;
    }

    /**
     * check time span perfect span is 17:00 - 17:00
     * 
     * @param int $timestr
     * @return string
     */
    public static function checkAbnormalTimespan($timestr)
    {
        // $perfect = strtotime(date('Y-m-d', $timestr).' 17:00:00');
        // if($perfect > $timestr) 
        // {
        //     if(($perfect - $timestr) < 5)
        //         return "编单结束时间异常，请手动干预处理！";
        // }
        return "";//"编单已完成，请加锁并审核！";
    }

}