<?php

namespace App\Tools;

use App\Models\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Notify
{

    public static $data;
    public static $ready = false;
    
    public static function writeAllNotificationToRedis()
    {
        self::$data = DB::table('notification')->selectRaw("`type`, count(`type`) as total")->where('viewed', 0)->groupBy('type')->pluck('total', 'type')->toArray();
    
        Cache::add('notify_total', self::getNotificationNumber());
        foreach(Notification::TYPES as $key=>$type)
            Cache::add("notify_$type", self::getNotificationNumber($key));

        Cache::add('notify_ready', 1);
    }

    public static function isReady()
    {
        return (int)Cache::get('notify_ready');
    }

    public static function getNotificationNumber($type=-1)
    {
        return $type==-1 ? array_sum(self::$data) : (key_exists($type, self::$data) ? self::$data[$type] : 0);
    }

    public static function fireNotify($group, $type, $name, $message='', $level='info')
    {
        $notify = new Notification();
        $notify->name = $name;
        $notify->type = $type;
        $notify->message = $message;
        $notify->level = $level;
        $notify->group_id = $group;
        $notify->save();
    }

    public static function readNotifications()
    {
        if(!self::isReady()) self::writeAllNotificationToRedis();
        $data = ['total'=>(int)Cache::get('notify_total')];
        foreach(Notification::TYPES as $key=>$type)
        {
            $data[$type] = (int)Cache::get("notify_$type");
        }
        return $data;
    }
}