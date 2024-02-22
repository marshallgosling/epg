<?php
namespace App\Tools;

use App\Models\Channel;
use App\Models\Material;
use App\Models\Program;
use App\Models\Record;
use App\Models\Record2;
use App\Models\Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Statistic
{
    
    public static function countChannelXml()
    {
        return DB::table('channel')->selectRaw('name, count(name) as total')->groupBy('name')->where('status', Channel::STATUS_READY)->pluck('total', 'name')->toArray();
    }

    public static function countTemplate()
    {
        return DB::table('template')->selectRaw('group_id, count(group_id) as total')->groupBy('group_id')->where('status', Template::STATUS_SYNCING)->pluck('total', 'group_id')->toArray();
    }

    public static function countMaterial()
    {
        return Cache::remember("material_status", 300, function() {
            $data = DB::table('material')->selectRaw('count(*) as count_num, `status`')
                    ->groupBy('status')->pluck('count_num', 'status')->toArray();
            if(!array_key_exists(Material::STATUS_ERROR, $data)) $data[Material::STATUS_ERROR] = 0;
            return $data;
        });
    }

    public static function countPrograms()
    {
        return Cache::remember("programs_status", 300, function() {
            return DB::table('program')->selectRaw('count(*) as count_num, `status`')
                    ->groupBy('status')->pluck('count_num', 'status')->toArray();
        });
    }

    public static function countRecords()
    {
        return Cache::remember("record_status", 300, function() {
            $data = DB::table('records')->selectRaw('count(*) as count_num, `status`')
                    ->groupBy('status')->pluck('count_num', 'status')->toArray();
            if(!array_key_exists(Record::STATUS_EMPTY, $data)) $data[Record::STATUS_EMPTY] = 0;
            return $data;
        });
    }

    public static function countRecord2()
    {
        return Cache()->remember("record2_status", 300, function() {
            return DB::table('record2')->selectRaw('count(*) as count_num, `status`')
                    ->groupBy('status')->pluck('count_num', 'status')->toArray();
        });
    }

    public static function countAudit()
    {
        return DB::table('channel')->selectRaw('name, count(name) as total')->groupBy('name')->where('audit_status', Channel::AUDIT_PASS)->pluck('total', 'name')->toArray();
    }

    public static function generatePieChart($id, $labels, $data, $title='')
    {
        $label = implode('\',\'', $labels);
        $data = implode(',', $data);
        return "new Chart(document.getElementById('$id'), {type:'pie',options:{plugins:{title:{display:true,text:'$title'}}},data: {labels: ['$label'], datasets:[{data:[$data],backgroundColor:colors}]}});";
    }
}