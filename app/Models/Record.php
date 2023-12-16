<?php

namespace App\Models;

use App\Tools\ChannelGenerator;
use App\Tools\Notify;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Temp\TemplateRecords;

class Record extends Model
{
    use HasFactory;

    protected $table = 'records';

    public const CATEGORIES = ['movie'=>'电影','starmade'=>'灿星制作','tvshow'=>'综艺','cartoon'=>'卡通','tvseries'=>'电视剧','docu'=>'纪实'];

    protected $fillable = [
        'id', 'name', 'unique_no','category', 'comment',
        'duration', 'air_date', 'expired_date', 'seconds',
        'ep', 'episodes', 'black'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getCategoryAttribute($value)
    {
        return explode(',', $value);
    }

    public function setCategoryAttribute($value)
    {
        $this->attributes['category'] = implode(',', $value);
    }

    private static $cache = [];
    private static $blacklist = [];
    private static $bumper = false;
    private static $pr = false;
    public static $daysofweek = '0';

    public static function loadBlackList()
    {
        self::$blacklist = BlackList::get()->pluck('keyword')->toArray();
    }

    public static function findRandom($key, $maxduration)
    {
        if(!Arr::exists(self::$cache, $key)) self::$cache[$key] = self::select('records.unique_no')->join('material', 'records.unique_no', '=', 'material.unique_no')->where('records.category','like',"%$key%")->pluck('unique_no')->toArray();

        if(!self::$cache[$key]) return false;   

        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);
        $id = Arr::random(self::$cache[$key]);
        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);

        $program = Record::where('records.unique_no', $id)
            ->join('material', 'records.unique_no', '=', 'material.unique_no')
            ->select("records.unique_no", "records.name", "records.episodes", "records.black", "material.duration", "material.frames")->first();

        $seconds = ChannelGenerator::parseDuration($program->duration);
        if($seconds > $maxduration) return self::findRandom($key, $maxduration);
        if($program && $program->black) return self::findRandom($key, $maxduration);
        else return $program;
    }

    /**
     * 根据模版数据寻找匹配的节目，并更新模版数据
     * 这里假设模版条件已经符合要求
     * 
     * @param TemplateRecords $template
     */
    public static function findNextAvaiable(TemplateRecords $template, int $maxduration) {
        if($template->category == 'movie')
            return self::findRandom($template->category, $maxduration);
       
        //$data = json_decode(json_encode($template->data));
        $data = $template->data;
        if($template->data['episodes'] == null) {
            $item = self::findRandomEpisode($template->category, $maxduration);
            
            $data['episodes'] = $item->episodes;
            $data['unique_no'] = $item->unique_no;
            $data['name'] = $item->name;
            $data['result'] = '编排中';

            $template->data = $data;
            $template->save();
            return $item;
        }

        $item = self::findNextEpisode($template->data['episodes'], $template->data['unique_no']);

        if($item == 'finished') {
            if($template->type == TemplateRecords::TYPE_STATIC) {
                Notify::fireNotify('xkc', Notification::TYPE_GENERATE, $template->data['episodes'].' 已播完，请确认是否换新', '', 'warning');

                $data['result'] = '编排完';
            }
            $item = false;
        }
        else if($item == 'empty') {
            if($template->type == TemplateRecords::TYPE_STATIC) {
                Notify::fireNotify('xkc', Notification::TYPE_GENERATE, $template->data['episodes'].' 没有找到任何剧集', '', 'error');

                $data['result'] = '未找到';
            }
            $item = false;
        }
        else {
            $data['episodes'] = $item->episodes;
            $data['unique_no'] = $item->unique_no;
            $data['result'] = '编排中';
            $data['name'] = $item->name;
        }

        $template->data = $data;
        $template->save();
 
        return $item;
    }

    public static function findNextEpisode($episodes, $unique_no='', $category='')
    {
        //if($episodes == null) return self::findRandomEpisode($category);
        $list = Record::where('episodes', $episodes)->orderBy('ep')->select('unique_no', 'name', 'episodes', 'black', 'duration')->get();
        foreach($list as $idx=>$l)
        {
            if($unique_no == '') return $l;
            if($l->unique_no == $unique_no) {
                $idx ++;
                if($idx == count($list)) {            
                    return 'finished';
                }
                else {
                    return $list[$idx];
                }
            }
        }
        return 'empty';
    }

    public static function findRandomEpisode($c, $maxduration)
    {
        $list = DB::table('records')->selectRaw('distinct(episodes)')->where('seconds','<',$maxduration)->where('ep', 1)->where('category', 'like', "%$c%")->get()->toArray();

        $list = Arr::shuffle($list);
        $list = Arr::shuffle($list);

        $name = $list[0];

        return self::findNextEpisode($name->episodes);

    }

    public static function loadBumpers($category='m1') {
        if(self::$bumper) return;

        self::$bumper = [];
        self::$bumper[] = Record::where('category', $category)->where('seconds','<=', 60)->select('unique_no')->pluck('unique_no')->toArray();
        self::$bumper[] = Record::where('category', $category)->where('seconds','>', 60)->where('seconds','<=', 300)->select('unique_no')->pluck('unique_no')->toArray();
        self::$bumper[] = Record::where('category', $category)->where('seconds','>', 300)->where('seconds','<=', 600)->select('unique_no')->pluck('unique_no')->toArray();
        self::$bumper[] = Record::where('category', $category)->where('seconds','>', 600)->where('seconds','<=', 1200)->select('unique_no')->pluck('unique_no')->toArray();
    }

    public static function findBumper($key) {
        self::$bumper[$key] = Arr::shuffle(self::$bumper[$key]);
        $id = Arr::random(self::$bumper[$key]);
        self::$bumper[$key] = Arr::shuffle(self::$bumper[$key]);

        $program = Record::where('records.unique_no', $id)
            ->join('material', 'records.unique_no', '=', 'material.unique_no')
            ->select("records.unique_no", "records.name", "records.episodes", "records.black", "material.duration", "material.frames")->first();

        if($program && $program->black) return self::findBumper($key);
        else return $program;
    }

    public static function findPR($category) {
        if(!self::$pr) self::$pr = Record::where('category', $category)->select('unique_no')->pluck('unique_no')->toArray();

        self::$pr = Arr::shuffle(self::$pr);
        $id = Arr::random(self::$pr);

        $program = Record::where('records.unique_no', $id)
        ->join('material', 'records.unique_no', '=', 'material.unique_no')
        ->select("records.unique_no", "records.name", "records.episodes", "records.black", "material.duration", "material.frames")->first();

        if($program && $program->black) return self::findPR($category);
        else return $program;
    }

    public static function findUnique($no)
    {
        return Record::where('records.unique_no', $no)
            ->join('material', 'records.unique_no', '=', 'material.unique_no')
            ->select("records.unique_no","records.name","records.episodes","material.duration","material.frames")->first();
    }

    public static function getTotal($key) {
        return Arr::exists(self::$cache, $key) ? count(self::$cache[$key]) : 0;
    }
}
