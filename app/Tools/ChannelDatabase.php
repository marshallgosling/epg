<?php

namespace App\Tools;

use App\Models\ChannelPrograms;
use App\Models\Epg;

class ChannelDatabase
{
    public static $channel;

    public static function removeEpg($channel)
    {
        Epg::where('channel_id', $channel->id)->delete();
    }

    public static function saveEpgToDatabase($channel)
    {
        if(!$channel) {
            echo "channel is null.".PHP_EOL;
            return;
        }

        $programs = $channel->programs()->get();
        //$last = strtotime($channel->air_date.' 00:00:00');
        echo "channel air date is {$channel->air_date}".PHP_EOL;
        $items = [];
        $air = 0;
        foreach($programs as $p)
        {
            echo "Program {$p->name} air date is {$p->start_at}".PHP_EOL;
            $item = [
                'group_id'=>$channel->name,
                'channel_id'=>$channel->id,
                'program_id'=>$p->id
            ];

            $air = strtotime($p->start_at);
            $data = json_decode($p->data);

            $replicate = 0;

            if(key_exists('replicate', $data)) {
                $replicate = $data->replicate;
                $data = ChannelPrograms::where('id', $replicate)->value('data');
                $data = json_decode($data);
            }

            foreach($data as $d) {
                $item['name'] = $d->name;
                $item['category'] = $d->category;
                $item['unique_no'] = $d->unique_no;
                $item['start_at'] = date('Y-m-d H:i:s', $air);
                $air += ChannelGenerator::parseDuration($d->duration);
                $item['end_at'] = date('Y-m-d H:i:s', $air);
                $item['duration'] = $d->duration;
                $item['comment'] = '';
                $items[] = $item;
            }
            
        }

        Epg::insert($items);
    }

    public static function fixChannelStartTime($channel)
    {
        if(!$channel) {
            echo "channel is null.".PHP_EOL;
            return;
        }

        $programs = $channel->programs()->get();
        $last = strtotime($channel->air_date.' 00:00:00');
        echo "channel air date is {$channel->air_date}".PHP_EOL;

        foreach($programs as $p)
        {
            echo "Program {$p->name} air date is {$p->start_at}".PHP_EOL;
            $air = strtotime($p->start_at);

            if($air < $last) {
                $air += 24 * 3600;
                
                echo "fix program: {$p->start_at} to ".date('Y-m-d H:i:s', $air).PHP_EOL;

                $p->start_at = date('Y-m-d H:i:s', $air);
                $p->save();
            }

            $last = $air;
        }
    }
}