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
use Encore\Admin\Facades\Admin;

class Record2 extends Model
{
    use HasFactory;

    protected $table = 'record2';

    public const CATEGORIES = ['movie'=>'电影','CanXin'=>'灿星制作','Entertainm'=>'综艺','cartoon'=>'卡通','drama'=>'电视剧','docu'=>'纪实'];
    public const XKC = ['CanXin'=>'灿星制作','Entertainm'=>'综艺','cartoon'=>'卡通','drama'=>'电视剧'];
    public const STATUS_EMPTY = 0;
    public const STATUS_READY = 1;
    public const STATUS_ERROR = 2;
    public const STATUS = ['不可用', '可用', '错误'];

    protected $fillable = [
        'id', 'name', 'name2', 'unique_no','category', 'comment',
        'duration', 'air_date', 'expired_date', 'seconds',
        'ep', 'episodes', 'black', 'status'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Model $model) {
            if(Admin::user()->cannot('delete-xkc'))
                throw new \Exception('您无权删除XKC节目库内容');
        });
    }

    public function getCategoryAttribute($value)
    {
        return explode(',', trim($value, ","));
    }

    public function setCategoryAttribute($value)
    {
        $this->attributes['category'] = implode(',', $value).',';
    }

    private static $cache = [];
    private static $blacklist = [];
    private static $bumper = false;
    private static $pr = false;
    private static $last_pr = false;
    private static $last_bumper = false;
    
    public static $daysofweek = '0';
    public static $islast = false;
    private static $expiration = [];
    public static $air = '2024-03-01';
    private static $_count = 3;

    public static function loadBlackList()
    {
        self::$blacklist = BlackList::get()->pluck('keyword')->toArray();
    }

    public static function loadExpiration($air)
    {
        $ids = Agreement::where('end_at', '<', $air)->pluck('id')->toArray();
        self::$expiration = Expiration::whereIn('agreement_id', $ids)->pluck('name')->toArray();
        self::$air = $air;
    }

    public static function cleanCache()
    {
        self::$bumper = [];
        self::$pr = null;
        self::$cache = [];
        self::$last_bumper = false;
    }

    public static function findRandom($key, $maxduration)
    {
        if(!Arr::exists(self::$cache, $key)) self::$cache[$key] = self::select('record2.unique_no')->join('material', 'record2.unique_no', '=', 'material.unique_no')->where('record2.category','like',"%$key,%")->pluck('unique_no')->toArray();

        self::$_count --;
        if(self::$_count < 0) { self::$_count = 3; return false; }
        if(!self::$cache[$key]) { self::$_count = 3; return false; }

        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);
        $id = Arr::random(self::$cache[$key]);
        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);

        $program = Record2::where('record2.unique_no', $id)
            ->join('material', 'record2.unique_no', '=', 'material.unique_no')
            ->select("record2.unique_no", "record2.name", "record2.episodes", "record2.black", "material.duration", "material.frames")->first();

        $seconds = ChannelGenerator::parseDuration($program->duration);
        if($seconds > $maxduration) return self::findRandom($key, $maxduration);
        if($program && $program->black) return self::findRandom($key, $maxduration);
        if(in_array($program->name, self::$expiration)) return self::findRandom($key, $maxduration);
        if(in_array($program->episodes, self::$expiration)) return self::findRandom($key, $maxduration);
        
        self::$_count = 3;
        return $program;
    }

    /**
     * 根据模版数据寻找匹配的节目，并更新模版数据
     * 这里假设模版条件已经符合要求
     * 
     * @param TemplateRecords $template
     */
    public static function findNextAvaiable(&$template, int $maxduration, int $air) {
        if($template->category == 'movie')
            return [self::findRandom($template->category, $maxduration)];
       
        
        $data = $template->data;
        $total = 1; $ep = 0;
        if(array_key_exists('ep', $data)) $total = (int)$data['ep'];
        $items = [];

        if($data['episodes'] == null) {

            $item = self::findRandomEpisode($template->category, $maxduration);
            // if($total == 1)
            // { 
            //     return [$item];
            // }

            if(!$item) return ['empty'];
            if(in_array($item, ['finished', 'empty'])) return [$item];

            $ep = 1;
            $data['episodes'] = $item->episodes;
            $data['unique_no'] = $item->unique_no;
            $data['result'] = "编排中";
            $items[] = $item;
        }

        if($data['result'] == '编排完') return ['finished'];
        
        $dayofweek = date('N', $air);
        if(array_key_exists('airday', $template->data) && count($template->data['airday']) && !in_array($dayofweek, $template->data['airday']))
        {
            // 有配置过首播日记录信息，且不是首播日，则会进入这段代码逻辑
            $item = self::findUnique($template->data['unique_no']);
            if(!$item) 
            {
                $data['result'] = '未找到';
                $item = 'empty2';
            }
            $items[] = $item;
        }
        else
        {
            for($i=$ep;$i<$total;$i++) {
                $item = self::findNextEpisode($data['episodes'], $data['unique_no']);

                if($item == 'finished') {
                    if($template->type == TemplateRecords::TYPE_STATIC) {
                        //Notify::fireNotify('xkc', Notification::TYPE_GENERATE, $template->data['episodes'].' 已播完，请确认是否换新', '', 'warning');
                    }
                    //$item = '编排完';
                    $data['result'] = '编排完';
                }
                else if($item == 'empty') {
                    if($template->type == TemplateRecords::TYPE_STATIC) {
                        //Notify::fireNotify('xkc', Notification::TYPE_GENERATE, $template->data['episodes'].' 没有找到任何剧集', '', 'error');
                    }
                    //$item = '未找到';
                    $data['result'] = '未找到';
                }
                else {
                    $data['episodes'] = $item->episodes;
                    $data['unique_no'] = $item->unique_no;
                    $data['result'] = '编排中';
                }
                
                $items[] = $item;
            }
        }
        return $items;
    }

    public static function findNextEpisode($episodes, $unique_no='', $category='')
    {
        //if($episodes == null) return self::findRandomEpisode($category);
        $list = Record2::where('episodes', $episodes)->orderBy('ep', 'asc')->select('unique_no', 'name', 'episodes', 'black', 'duration')->get();
        self::$islast = false;
        foreach($list as $idx=>$l)
        {
            if($unique_no == '') return $l;
            if($l->unique_no == $unique_no) {
                $idx ++;
                if($idx == count($list)) {            
                    return 'finished';
                }
                else {
                    if($idx == count($list)-1) self::$islast = true;
                    return $list[$idx];
                }
            }
        }
        return 'empty';
    }

    public static function findRandomEpisode($c, $maxduration)
    {
        $list = DB::table('record2')->selectRaw('distinct(episodes)')
            ->where('seconds','<',$maxduration)
            ->where('category', 'like', "%$c,%")->get()->toArray();

        self::$_count --;
        if(self::$_count < 0) { self::$_count = 3; return false; }
        if(!$list) { self::$_count = 3; return false; }

        $list = Arr::shuffle($list);
        $list = Arr::shuffle($list);

        $name = $list[0];

        if(in_array($name->episodes, self::$expiration)) return self::findRandomEpisode($c, $maxduration);

        self::$_count = 3;
        return self::findNextEpisode($name->episodes);

    }

    public static function loadBumpers($category='m1') {
        if(self::$bumper) return;

        self::$bumper = [];
        self::$bumper[] = Record2::where('record2.category', $category.',')->join('material', 'record2.unique_no', '=', 'material.unique_no')->where('seconds','<=', 60)->select('record2.unique_no')->pluck('unique_no')->toArray();
        self::$bumper[] = Record2::where('record2.category', $category.',')->join('material', 'record2.unique_no', '=', 'material.unique_no')->where('seconds','>', 60)->where('seconds','<=', 300)->select('record2.unique_no')->pluck('unique_no')->toArray();
        self::$bumper[] = Record2::where('record2.category', $category.',')->join('material', 'record2.unique_no', '=', 'material.unique_no')->where('seconds','>', 300)->where('seconds','<=', 600)->select('record2.unique_no')->pluck('unique_no')->toArray();
        self::$bumper[] = Record2::where('record2.category', $category.',')->join('material', 'record2.unique_no', '=', 'material.unique_no')->where('seconds','>', 600)->where('seconds','<=', 1200)->select('record2.unique_no')->pluck('unique_no')->toArray();
    }

    public static function findBumper($key) {
        self::$bumper[$key] = Arr::shuffle(self::$bumper[$key]);
        $id = Arr::random(self::$bumper[$key]);
        self::$bumper[$key] = Arr::shuffle(self::$bumper[$key]);
        if(self::$last_bumper == $id) return self::findBumper($key);
        self::$last_bumper = $id;

        $program = Record2::where('record2.unique_no', $id)
            ->join('material', 'record2.unique_no', '=', 'material.unique_no')
            ->select("record2.unique_no", "record2.name", "record2.episodes", "record2.black", "material.duration", "material.frames")->first();

        if($program && $program->black) return self::findBumper($key);
        else return $program;
    }

    public static function checkBumperAndPr() {
        $bum = config('XKC_BUMPERS_TAG', 'XK FILLER');
        $c = 0;
        self::loadBumpers($bum);
        
        if(self::$bumper)foreach(self::$bumper as $b)
            $c += count($b);

        $p = config('XKC_PR_TAG', 'XK PR');
        $pr = Record::where('records.category', $p.',')->join('material', 'records.unique_no', '=', 'material.unique_no')->select('records.unique_no')->pluck('unique_no')->toArray();

        $c2 = count($pr);

        return [$bum=>$c, $p=>$c2];
    }

    public static function findPR($category) {
        if(!self::$pr) self::$pr = Record2::where('record2.category', $category.',')->join('material', 'record2.unique_no', '=', 'material.unique_no')->select('record2.unique_no')->pluck('unique_no')->toArray();

        self::$pr = Arr::shuffle(self::$pr);
        $id = Arr::random(self::$pr);
        if(self::$last_pr == $id) return self::findPR($category);
        self::$last_pr = $id;

        $program = Record2::where('record2.unique_no', $id)
        ->join('material', 'record2.unique_no', '=', 'material.unique_no')
        ->select("record2.unique_no", "record2.name", "record2.episodes", "record2.black", "material.duration", "material.frames")->first();

        if($program && $program->black) return self::findPR($category);
        else return $program;
    }

    public static function findUnique($no)
    {
        return Record2::where('record2.unique_no', $no)
            ->join('material', 'record2.unique_no', '=', 'material.unique_no')
            ->select("record2.unique_no","record2.name","record2.episodes","material.duration","material.frames")->first();
    }

    public static function getTotal($key) {
        return Arr::exists(self::$cache, $key) ? count(self::$cache[$key]) : 0;
    }
}
