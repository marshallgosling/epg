<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
class Program extends Model
{
    use HasFactory;

    protected $table = 'program';

    public const STATUS_EMPTY = 0;
    public const STATUS_READY = 1;
    public const STATUS_ERROR = 2;
    public const STATUS = ['不可用', '可用'];

    protected $fillable = [
        'id', 'name', 'unique_no','category', 'comment',
        'album','artist','co_artist', 'duration', 'status',
        'company', 'air_date', 'product_date', 'seconds',
        'genre', 'gender','lang','mood','tempo','energy', 'black'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Model $model) {
            if(Admin::user()->cannot('delete-xkv'))
                throw new \Exception('您无权删除XKV节目库内容');
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
    private static $last = [];
    private static $_count = 3;

    public static function loadBlackList()
    {
        self::$blacklist = BlackList::get()->pluck('keyword')->toArray();
    }

    public static function clearCache()
    {
        self::$cache = [];
        self::$_count = 3;
        self::$last = [];
    }

    public static function findRandom($key, $maxSeconds)
    {
        if(!Arr::exists(self::$cache, $key)) 
            self::$cache[$key] = self::select('program.unique_no')
                ->join('material', 'program.unique_no', '=', 'material.unique_no')
                ->where('program.category','like',"%$key,%")
                ->where('program.seconds','<',$maxSeconds)
                ->where('program.status', Program::STATUS_READY)
                ->where('program.black', 0)
                ->pluck('unique_no')->toArray();

        if(!self::$cache[$key]) {
            self::$_count = 3;
            return false;   
        }

        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);
        $id = Arr::random(self::$cache[$key]);
        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);

        //if($id == self::$last) {
        if(in_array($id, self::$last)) {
            return self::findRandom($key, $maxSeconds);
        }

        self::$_count --;
        if(self::$_count < 0) { self::$_count = 3; return false; }
        //if(!$list) { self::$_count = 3; return false; }

        $program = Program::where('program.unique_no', $id)
            ->join('material', 'program.unique_no', '=', 'material.unique_no')
            ->select("program.unique_no", "program.name", "program.artist", "program.black", "material.duration","material.frames")->first();

        // if($program->black) return self::findRandom($key, $maxSeconds);
        // else {
            self::$_count = 3;
            self::$last[] = $id;
            return $program;
        // }
    }

    public static function loadBumpers() {

    }

    public static function findBumper($key) {

    }

    public static function findUnique($no)
    {
        return Program::where('program.unique_no', $no)
            ->join('material', 'program.unique_no', '=', 'material.unique_no')
            ->select("program.unique_no","program.name","program.artist","material.duration","material.frames")->first();
    }

    public static function getTotal($key) {
        return Arr::exists(self::$cache, $key) ? count(self::$cache[$key]) : 0;
    }
}
