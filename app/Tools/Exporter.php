<?php

namespace App\Tools;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use Faker\ChanceGenerator;
use Illuminate\Support\Facades\DB;

class Exporter
{
    private static $json;
    private static $xml;

    public static function generateSimple($channel, $programs)
    {
        $jsonstr = Storage::disk('data')->get('template.json');

        $json = json_decode($jsonstr);

        $json->ChannelName = $channel->name;
        $json->PgmDate = $channel->air_date;
        $json->Version = $channel->version;
        $json->Count = count($programs);

        foreach($programs as $idx=>$program)
        {
            $date = Carbon::parse($channel->air_date . ' '. $program->start_at);
            // if not exist, just copy one 
            if(!array_key_exists($idx, $json->ItemList)) {
                $json->ItemList[] = clone $json->ItemList[$idx-1];
                $cl = [$json->ItemList[$idx]->ClipsItem[0]];
                $json->ItemList[$idx]->ClipsItem = $cl;

                $itemList = &$json->ItemList[$idx];

                $start = ChannelPrograms::caculateFrames($date->format('H:i:s'));
                $duration = ChannelPrograms::caculateFrames($program->duration);
                        
                    $itemList->StartTime = $start;
                    $itemList->SystemTime = $date->format('Y-m-d H:i:s');
                    $itemList->Name = $program->name;
                    $itemList->BillType = $date->format('md').'新建';
                    $itemList->LimitLen = $duration;
                    $itemList->PgmDate = $date->diffInDays(Carbon::parse('1899-12-30 00:00:00'));
                    $itemList->PlayType = $idx == 0 ? 1 : 0;

                $clips = &$itemList->ClipsItem;
                $n = 0;
                $c = &$clips[$n];
                $c->FileName = $program->unique_no;
                $c->Name = $program->name;
                $c->Id = $program->unique_no;
                $c->LimitDuration = $duration;
                $c->Duration = $duration;              

                //$duration += ChannelPrograms::caculateSeconds($program->duration);

                $itemList->Length = $duration;
                $itemList->LimitLen = $duration;
                $itemList->ID = (string)Str::uuid();
                $itemList->Pid = (string)Str::uuid();
                $itemList->ClipsCount = 1;
            }
        }

        self::$json = $json;
    }

    public static function generate($id)
    {
        $jsonstr = Storage::disk('data')->get('template.json');

        $json = json_decode($jsonstr);

        $channel = Channel::find($id);

        $json->ChannelName = $channel->name;
        $json->PgmDate = $channel->air_date;
        $json->Version = $channel->version;

        $programs = $channel->programs()->get();

        $json->Count = count($programs);

        foreach($programs as $idx=>$program)
        {
            $date = Carbon::parse($program->start_at);
            // if not exist, just copy one 
            if(!array_key_exists($idx, $json->ItemList)) {
                $json->ItemList[] = clone $json->ItemList[$idx-1];
                $cl = [$json->ItemList[$idx]->ClipsItem[0]];
                $json->ItemList[$idx]->ClipsItem = $cl;
            }

            $itemList = &$json->ItemList[$idx];

            $start = ChannelPrograms::caculateFrames($date->format('H:i:s'));
                       
                $itemList->StartTime = $start;
                $itemList->SystemTime = $date->format('Y-m-d H:i:s');
                $itemList->Name = $program->name;
                $itemList->BillType = $date->format('md').'新建';
                $itemList->LimitLen = ChannelPrograms::caculateFrames($program->duration);
                $itemList->PgmDate = $date->diffInDays(Carbon::parse('1899-12-30 00:00:00'));
                $itemList->PlayType = $idx == 0 ? 1 : 0;

            $clips = &$itemList->ClipsItem;
            $data = json_decode($program->data);
            $duration = 0;
            if(is_array($data)) foreach($data as $n=>$clip)
            { 
                if(!array_key_exists($n, $clips)) $clips[$n] = clone $clips[$n-1];
                
                $c = &$clips[$n];
                $c->FileName = $clip->unique_no;
                $c->Name = $clip->name;
                $c->Id = $clip->unique_no;
                $c->LimitDuration = ChannelPrograms::caculateFrames($clip->duration);
                $c->Duration = ChannelPrograms::caculateFrames($clip->duration);              

                $duration += ChannelPrograms::caculateSeconds($clip->duration);
            }
            $itemList->Length = $duration * config('FRAME', 25);
            $itemList->LimitLen = $duration * config('FRAME', 25);
            $itemList->ID = (string)Str::uuid();
            $itemList->Pid = (string)Str::uuid();
            $itemList->ClipsCount = is_array($data) ? count($data) : 0;

            //break;
        }

        self::$json = $json;
    }

    public static function exportXml($file=false)
    {
        $exporter = new \App\Tools\XmlWriter();
        self::$xml = $exporter->export(self::$json, 'PgmItem');

        if($file) {
            Storage::disk('public')->put(self::$json->ChannelName.'_'.self::$json->PgmDate.'.xml', self::$xml);

        }
        return self::$xml;
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

        echo "find channel: ".count($channels).PHP_EOL;

        if($channels)foreach($channels as $channel)
        {
            $programs = ChannelPrograms::where('channel_id', $channel->id)->orderBy('id')->get();
            //$air = strtotime($channel->air_date.' 06:00:00');
            $air = $channel->air_date;
            if($programs)foreach($programs as $p)
            {
                echo "find program: ".$p->name.PHP_EOL;
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
                    echo "find item: ".$item->name.PHP_EOL;

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

    public static function gatherData($air_date, $group) 
    {
        
        $end_at = $air_date;
        $start_at = date('Y-m-d', (strtotime($end_at.' 06:00:00') - 86400));

        echo "$start_at - $end_at".PHP_EOL;

        $lines = self::gatherLines($start_at, $end_at, $group, 'xml');

        print_r($lines);
        
        $found = false;
        $data = [];

        foreach($lines as $l)
        {
            if($l->start_at == '06:00:00') $found = true;
            if($found) $data[] = $l;
            if($l->end_at == '06:00:00') $found = false;
        }

        return $data;

    }



}