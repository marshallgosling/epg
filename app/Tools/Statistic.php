<?php
namespace App\Tools;

use App\Models\Channel;
use App\Models\Program;
use App\Models\Record;
use App\Models\Record2;
use App\Models\Template;
use Illuminate\Support\Facades\DB;

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

    public static function countPrograms()
    {
        return Program::count();
    }

    public static function countRecords()
    {
        return Record::count();
    }

    public static function countRecord2()
    {
        return Record2::count();
    }

    public static function countAudit()
    {
        return DB::table('channel')->selectRaw('name, count(name) as total')->groupBy('name')->where('audit_status', Channel::AUDIT_PASS)->pluck('total', 'name')->toArray();
    }
}