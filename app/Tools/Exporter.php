<?php

namespace App\Tools;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Epg;
use Illuminate\Support\Facades\DB;

class Exporter
{
    private static $json;
    private static $xml;
    public static $file = true;

    public const TIMES = ['xkv'=>'06:00:00', 'xkc'=>'17:00:00'];

    public static function generateSimple($channel, $programs)
    {
        $jsonstr = Storage::disk('data')->get('template.json');

        $template = json_decode($jsonstr);

        $json = clone $template->PgmItem;
        $json->ChannelName = $channel->name;
        $json->PgmDate = $channel->air_date;
        $json->Version = $channel->version;
        $json->Count = count($programs);

        foreach($programs as $idx=>$program)
        {
            $date = Carbon::parse($channel->air_date . ' '. $program->start_at);
            
            $itemList = clone $template->ItemList;

                $start = ChannelPrograms::caculateFrames($date->format('H:i:s'));
                $duration = ChannelPrograms::caculateFrames($program->duration);
                        
                    $itemList->StartTime = $start;
                    $itemList->SystemTime = $date->format('Y-m-d H:i:s');
                    $itemList->Name = '<![CDATA['.$program->name.']]>';
                    $itemList->BillType = $date->format('md').'新建';
                    $itemList->LimitLen = $duration;
                    $itemList->PgmDate = $date->diffInDays(Carbon::parse('1899-12-30 00:00:00'));
                    $itemList->PlayType = $idx == 0 ? 1 : 0;

                $clip = clone $template->ClipsItem;
                $clip->FileName = '<![CDATA['.$program->name.'.'.$program->unique_no.']]>';
                $clip->Name = '<![CDATA['.$program->name.']]>';
                $clip->Id = $program->unique_no;
                $clip->LimitDuration = $duration;
                $clip->Duration = $duration;

                $itemList->Length = $duration;
                $itemList->LimitLen = $duration;
                $itemList->ID = (string)Str::uuid();
                $itemList->Pid = (string)Str::uuid();
                $itemList->ClipsCount = 1;
                $itemList->ClipsItem[] = $clip;

                $json->ItemList[] = $itemList;
        }
        

        self::$json = $json;
    }

    public static function generate($id)
    {
        $jsonstr = Storage::disk('data')->get('template.json');

        $template = json_decode($jsonstr);

        $channel = Channel::find($id);

        $json = clone $template->PgmItem;
        $json->ChannelName = $channel->name;
        $json->PgmDate = $channel->air_date;
        $json->Version = $channel->version;

        $programs = $channel->programs()->get();

        $json->Count = count($programs);

        foreach($programs as $idx=>$program)
        {
            $date = Carbon::parse($program->start_at);

            $itemList = clone $template->ItemList;

            $start = ChannelPrograms::caculateFrames($date->format('H:i:s'));
                       
                $itemList->StartTime = $start;
                $itemList->SystemTime = $date->format('Y-m-d H:i:s');
                $itemList->Name = $program->name;
                $itemList->BillType = $date->format('md').'新建';
                $itemList->LimitLen = ChannelPrograms::caculateFrames($program->duration);
                $itemList->PgmDate = $date->diffInDays(Carbon::parse('1899-12-30 00:00:00'));
                $itemList->PlayType = $idx == 0 ? 1 : 0;

            $data = json_decode($program->data);
            $duration = 0;
            if(is_array($data)) foreach($data as $item)
            { 
                $clip = clone $template->ClipsItem;
                
                $clip->FileName = $item->unique_no;
                $clip->Name = $item->name;
                $clip->Id = $item->unique_no;
                $clip->LimitDuration = ChannelPrograms::caculateFrames($item->duration);
                $clip->Duration = ChannelPrograms::caculateFrames($item->duration);              

                $duration += ChannelPrograms::caculateSeconds($item->duration);
                $itemList->ClipsItem[] = $clip;
            }
            $itemList->Length = $duration * config('FRAME', 25);
            $itemList->LimitLen = $duration * config('FRAME', 25);
            $itemList->ID = (string)Str::uuid();
            $itemList->Pid = (string)Str::uuid();
            $itemList->ClipsCount = is_array($data) ? count($data) : 0;

            $json->ItemList[] = $itemList;
        }

        self::$json = $json;
    }

    public static function exportXml($json=false, $name=false)
    {
        $exporter = new \App\Tools\XmlWriter();
        if(!$json) $json = self::$json;

        $xml = $exporter->export($json, 'PgmItem');

        if(!$name) $name = $json->ChannelName;

        if(self::$file) {
            Storage::disk('public')->put($name.'_'.$json->PgmDate.'.xml', $xml);

        }
        self::$xml = $xml;
        return $xml;
    }

    public static function gatherLines($start_at, $end_at, $group_id, $mode='excel')
    {
        
        $channels = DB::table('channel')->whereBetween('air_date', [$start_at, $end_at])
                                        ->where('name', $group_id)
                                        ->where('status', Channel::STATUS_READY)
                                        // ->where('audit_status', Channel::AUDIT_PASS)
                                        ->select('id','air_date','uuid')
                                        ->orderBy('air_date')->get();

        $no = 1;
        $lines = [];

        //echo "find channel: ".count($channels).PHP_EOL;

        if($channels)foreach($channels as $channel)
        {
            $programs = ChannelPrograms::where('channel_id', $channel->id)->orderBy('id')->get();
            //$air = strtotime($channel->air_date.' 06:00:00');
            $air = $channel->air_date;
            if($programs)foreach($programs as $p)
            {
                //echo "find program: ".$p->name.PHP_EOL;
                $items = json_decode($p->data);
                if(array_key_exists('replicate', $items)) {
                    $items = json_decode(
                        ChannelPrograms::where('id', $items->replicate)->value('data')
                    );
                    $items = ChannelGenerator::caculateDuration($items, strtotime($p->start_at));
                }
                if($items)foreach($items as $item)
                {
                    // $end = $air + ChannelGenerator::parseDuration($item->duration);
                    // $l = [
                    //     $no, $p->name, $item->name, $item->unique_no, date('y-m-d', $air),
                    //     date('H:i:s', $air).':00', date('H:i:s', $end).':00', $item->duration, '00:00:00:00', ''
                    // ];
                    //echo "find item: ".$item->name.PHP_EOL;

                    if($mode == 'excel') {
                        $l = ChannelGenerator::createExcelItem($item, $p->name, $no, $air);
                        $no ++;
                        $lines[] = $l;
                    }

                    if($mode == 'xml') {
                        
                        $lines[] = $item;
                    }
                    
                }

                
                
            }
        }

        return $lines;
    }

    public static function collectData($air_date, $group, \Closure $callback=null)
    {
        $data = [];
        $start_at = strtotime($air_date.' 00:00:00');

        $list = Epg::where('group_id', $group)
                    ->where('start_at','>',date('Y-m-d H:i:s', $start_at))
                    ->where('start_at','<',date('Y-m-d H:i:s', $start_at+86400+24800))
                    ->orderBy('start_at', 'asc')->get();

        $pos_start = strtotime($air_date.' '.config('EPG_START_AT', '06:00:00'));
        $pos_end = $pos_start + 86400;

        $begin = $end = 0;
        foreach($list as $idx=>$item) {
            $start = strtotime($item->start_at);

            if($start > ($pos_start-300) && $start < ($pos_start + 300)) $begin=$idx;
            if($start > ($pos_end-300) && $start < ($pos_end + 300)) $end = $idx;
        }
        foreach($list as $idx=>$item) {

            if($idx < $begin || $idx>=$end) {
                continue;
            }
            if($callback)
                $data[] = call_user_func($callback, $item);
            else
                $data[] = $item->toArray();
        }

        return $data;
    }

    public static function collectData2($air_date, $group, \Closure $callback=null) 
    {      
        $data = [];
        $order = [];
        
        $start_at = strtotime($air_date.' '.config('EPG_START_AT', '06:00:00'));
        $pos_start = (int)Epg::where('group_id', $group)
                        ->where('start_at','>',date('Y-m-d H:i:s', $start_at-300))
                        ->where('start_at','<',date('Y-m-d H:i:s', $start_at+300))
                        ->orderBy('start_at', 'desc')->limit(1)->value('id');
        $start_at += 86400;
        $air_date = date('Y-m-d', $start_at);
        $pos_end = (int)Epg::where('group_id', $group)
                        ->where('start_at','>',date('Y-m-d H:i:s', $start_at-300))
                        ->where('start_at','<',date('Y-m-d H:i:s', $start_at+300))
                        ->orderBy('start_at', 'desc')->limit(1)->value('id');

        if($pos_start>=0 && $pos_end>$pos_start)
        {
            $list = Epg::where('group_id', $group)->where('id', '>=', $pos_start)->where('id','<',$pos_end)->get();

            $programs = DB::table('epg')->selectRaw('distinct(program_id)')
                            ->where('id', '>=', $pos_start)->where('id','<',$pos_end)
                            ->pluck('program_id')->toArray();

            $programs = ChannelPrograms::select('id','name','start_at','end_at','schedule_start_at','schedule_end_at','duration')
                            ->whereIn('id', $programs)->orderBy('start_at')->get();
    
            foreach($programs as $pro)
            {
                $data[$pro->id] = $pro->toArray();
                $data[$pro->id]['items'] = [];
                $order[] = $pro->id;
            }
    
            foreach($list as $t) {
                if($callback)
                    $data[$t->program_id]['items'][] = call_user_func($callback, $t);
                else
                    $data[$t->program_id]['items'][] = $t->toArray();
            }
            
        }

        $data['order'] = $order;

        return $data;

    }

    public static function generateData($channel, $data, $fixDate = false)
    {
        $jsonstr = Storage::disk('data')->get('template.json');

        $template = json_decode($jsonstr);

        if(!$fixDate) $fixDate = $channel->air_date;
        $json = clone $template->PgmItem;

        $json->ChannelName = $channel->name;
        $json->PgmDate = $fixDate;
        $json->Version = $channel->version;

        $json->Count = count($data);

        foreach($data as $idx=>$program)
        {
            //$program = $data[$pid];
            $date = Carbon::parse($program['start_at']);

            $itemList = clone $template->ItemList;

            $start = ChannelPrograms::caculateFrames($date->format('H:i:s'));
                       
                $itemList->StartTime = $start;
                $itemList->SystemTime = $date->format('Y-m-d H:i:s');
                $itemList->Name = $program['name'];
                $itemList->BillType = $date->format('md').'新建';
                $itemList->LimitLen = 0;
                $itemList->PgmDate = $date->diffInDays(Carbon::parse('1899-12-30 00:00:00'));
                $itemList->PlayType = $idx == 0 ? 1 : 0;

            //$clips = ;
            //$items = $program['items'];
            $duration = 0;
            //if(is_array($items)) foreach($items as $item)
            { 
                $clip = clone $template->ClipsItem;
                $clip->FileName = '<![CDATA['.$program['name'].'.'.$program['unique_no'].']]>';
                $clip->Name = '<![CDATA['.$program['name'].']]>';
                $clip->Id = $program['unique_no'];
                $seconds = ChannelPrograms::caculateSeconds($program['duration']);
                $frames = $seconds * (int)config('FRAMES', 25);
                $clip->LimitDuration = $frames;
                $clip->Duration = $frames;
                $duration += $seconds;
                $itemList->ClipsItem[] = $clip;
            }
            $itemList->Length = $duration * config('FRAME', 25);
            $itemList->LimitLen = $duration * config('FRAME', 25);
            $itemList->ID = (string)Str::uuid();
            $itemList->Pid = (string)Str::uuid();
            $itemList->ClipsCount = 1;
            $json->ItemList[] = $itemList;
        }

        self::$json = $json;

        return $json;
    
    }

    public static function generateData2($channel, $data, $fixDate = false)
    {
        $jsonstr = Storage::disk('data')->get('template.json');

        $template = json_decode($jsonstr);

        if(!$fixDate) $fixDate = $channel->air_date;
        $json = clone $template->PgmItem;

        $json->ChannelName = $channel->name;
        $json->PgmDate = $fixDate;
        $json->Version = $channel->version;

        $json->Count = count($data) - 1;

        foreach($data['order'] as $idx=>$pid)
        {
            $program = $data[$pid];
            $date = Carbon::parse($fixDate. ' ' .$program['start_at']);

            $itemList = clone $template->ItemList;

            $start = ChannelPrograms::caculateFrames($date->format('H:i:s'));
                       
                $itemList->StartTime = $start;
                $itemList->SystemTime = $date->format('Y-m-d H:i:s');
                $itemList->Name = $program['name'];
                $itemList->BillType = $date->format('md').'新建';
                $itemList->LimitLen = 0;
                $itemList->PgmDate = $date->diffInDays(Carbon::parse('1899-12-30 00:00:00'));
                $itemList->PlayType = $idx == 0 ? 1 : 0;

            //$clips = ;
            $items = $program['items'];
            $duration = 0;
            if(is_array($items)) foreach($items as $item)
            { 
                $clip = clone $template->ClipsItem;
                $clip->FileName = '<![CDATA['.$item['name'].'.'.$item['unique_no'].']]>';
                $clip->Name = '<![CDATA['.$item['name'].']]>';
                $clip->Id = $item['unique_no'];
                $seconds = ChannelPrograms::caculateSeconds($item['duration']);
                $frames = $seconds * (int)config('FRAMES', 25);
                $clip->LimitDuration = $frames;
                $clip->Duration = $frames;
                $duration += $seconds;
                $itemList->ClipsItem[] = $clip;
            }
            $itemList->Length = $duration * config('FRAME', 25);
            $itemList->LimitLen = $duration * config('FRAME', 25);
            $itemList->ID = (string)Str::uuid();
            $itemList->Pid = (string)Str::uuid();
            $itemList->ClipsCount = is_array($items) ? count($items) : 0;
            $json->ItemList[] = $itemList;
        }

        self::$json = $json;

        return $json;
    }


}