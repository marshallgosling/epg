<?php

namespace App\Tools;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\ChannelPrograms;

class ProgramsExporter
{
    private static $json;
    private static $xml;

    public static function generate($id)
    {
        $jsonstr = Storage::disk('data')->get('template.json');

        $json = json_decode($jsonstr);

        $channel = \App\Models\Channel::find($id);

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
        $exporter = new \App\Tools\XmlExporter();
        self::$xml = $exporter->export(self::$json, 'PgmItem');

        if($file) {
            Storage::disk('public')->put(self::$json->ChannelName.'_'.self::$json->PgmDate.'.xml', self::$xml);

        }
        return self::$xml;
    }


}