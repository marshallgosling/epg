<?php

namespace App\Tools\Exporter;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Epg;
use App\Models\Material;
use App\Models\Plan;
use App\Tools\ChannelGenerator;
use Illuminate\Support\Facades\DB;

class ExcelExporter
{
    private static $json;
    private static $xml;
    public static $file = true;

    public const TIMES = ['xkv'=>'06:00:00', 'xkc'=>'17:00:00'];
    public const NAMES = ['xkc'=>'XKC','xki'=>'XKI','xkv'=>'CNV'];

    public static function collectData($start_at, $end_at, $group, \Closure $callback=null)
    {
        $data = [];

        $list = Epg::where('group_id', $group)
                    ->where('start_at','>=',$start_at)
                    ->where('start_at','<=',$end_at)
                    ->orderBy('start_at', 'asc')->lazy();

        $data = [];
        foreach($list as $idx=>$item) {

            if($callback)
                $data[] = call_user_func($callback, $item);
            else
                $data[] = $item->toArray();
        }

        return $data;
    }

}