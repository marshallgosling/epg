<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Record extends Model
{
    use HasFactory;

    protected $table = 'records';

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
    private static $bumper = [];

    public static function loadBlackList()
    {
        self::$blacklist = BlackList::get()->pluck('keyword')->toArray();
    }

    public static function findRandom($key)
    {
        if(!Arr::exists(self::$cache, $key)) self::$cache[$key] = self::select('records.unique_no')->join('material', 'records.unique_no', '=', 'material.unique_no')->where('records.category','like',"%$key%")->pluck('unique_no')->toArray();

        if(!self::$cache[$key]) return false;   

        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);
        $id = Arr::random(self::$cache[$key]);
        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);

        $program = Record::where('records.unique_no', $id)
            ->join('material', 'records.unique_no', '=', 'material.unique_no')
            ->select("records.unique_no", "records.name", "records.episodes", "records.black", "material.duration", "material.frames")->first();

        if($program && $program->black) return self::findRandom($key);
        else return $program;
    }

    public static function findNextEpisode($name, $code='', $category='')
    {
        $list = Record::where('episodes', $name)->orderBy('ep')->select('unique_no', 'name', 'episodes', 'black', 'duration')->get();
        foreach($list as $idx=>$l)
        {
            if($code == '') return $l;
            if($l->unique_no == $code) {
                $idx ++;
                if($idx == count($l)) {
                    return false;
                }
                else {
                    return $list[$idx];
                }
            }
        }
        return false;
    }

    public static function findRandomEpisode($c)
    {
        $list = DB::table('records')->selectRaw('distinct(episodes)')->where('category', 'like', "%$c%")->get()->toArray();

        $list = Arr::shuffle($list);
        $list = Arr::shuffle($list);

        $name = $list[0];

        return self::findNextEpisode($name->episodes);

    }

    public static function loadBumpers($category='m1') {
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
