<?php

namespace App\Tools;


use App\Models\ChannelPrograms;
use App\Models\Program;
use App\Models\Temp\Template;
use App\Models\Temp\TemplateRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


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

}