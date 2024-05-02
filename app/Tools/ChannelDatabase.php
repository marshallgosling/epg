<?php

namespace App\Tools;

use App\Models\ChannelPrograms;
use App\Models\Epg;
use App\Models\Notification;

class ChannelDatabase
{
    public static $channel;

    public static function removeEpg($channel)
    {
        Epg::where('channel_id', $channel->id)->delete();
    }

    public static function checkEpgWithChannel($channel)
    {
        $msg = 'channel is null.';
        $result = true;
        $items = [];

        if(!$channel) {
            return compact('result', 'msg', 'items');
        }

        $epglist = Epg::where('channel_id', $channel->id)->select('name','category','unique_no','start_at','end_at','duration')->get()->toArray();

        $programs = $channel->programs()->get();

        $air = 0;
        $key = 0;
        foreach($programs as $p)
        {
            //echo "Program {$p->name} air date is {$p->start_at}".PHP_EOL;
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
            }
            
            if(!array_key_exists($key, $epglist)) 
            {
                $result = false;
                $msg = "EPG key:$key 不存在，EPG串联单长度不足";
                $items[] = $item;
                break;
            }
            $st1 = implode('|',$item);
            $st2 = implode('|',$epglist[$key]);

            if($st1 != $st2) {
                $result = false;
                $msg = "EPG串联单数据不匹配";
                $items[] = $item;
                $items[] = $epglist[$key];
                break;
            }
            $key ++;
        }

        if($result && $key < count($epglist))
        {
            $result = false;
            $msg = "EPG串联单数据不匹配";
            $items[] = $item;
            $items[] = $epglist[$key];
        }

        return compact('result', 'msg', 'items');
    }

    public static function saveEpgToDatabase($channel)
    {
        if(!$channel) {
            echo "channel is null.".PHP_EOL;
            return;
        }

        $programs = $channel->programs()->get();
        //$last = strtotime($channel->air_date.' 00:00:00');
        //echo "channel air date is {$channel->air_date}".PHP_EOL;
        $items = [];
        $air = 0;
        foreach($programs as $p)
        {
            //echo "Program {$p->name} air date is {$p->start_at}".PHP_EOL;
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

        Notify::fireNotify(
            $channel->name,
            Notification::TYPE_EPG, 
            "{$channel->name} {$channel->air_date} 串联单更新", "频道 {$channel->name} 日期 {$channel->air_date} 串联单数据更新并将替换已有数据（如存在）。"
        );
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