<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
class Program extends Model
{
    use HasFactory;

    protected $table = 'program';

    protected $fillable = [
        'id', 'name', 'unique_no','category', 'comment',
        'album','artist','co_artist', 'duration',
        'company', 'air_date', 'product_date', 
        'genre', 'gender','lang','mood','tempo','energy', 'black'
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

    public static function loadBlackList()
    {
        self::$blacklist = BlackList::get()->pluck('keyword')->toArray();
    }

    public static function findRandom($key)
    {
        if(!Arr::exists(self::$cache, $key)) self::$cache[$key] = self::select('program.unique_no')->join('material', 'program.unique_no', '=', 'material.unique_no')->where('program.category','like',"%$key%")->pluck('unique_no')->toArray();

        if(!self::$cache[$key]) return false;   

        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);
        $id = Arr::random(self::$cache[$key]);
        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);

        $program = Program::where('program.unique_no', $id)
            ->join('material', 'program.unique_no', '=', 'material.unique_no')
            ->select("program.unique_no", "program.name", "program.artist", "program.black", "material.duration","material.frames")->first();

        // $black = false;

        // if($program->artist)foreach(self::$blacklist as $keyword) {
        //     if(Str::contains($program->artist, $keyword)) {
        //         $black = true;
        //         break;
        //     }
        // };

        if($program->black) return self::findRandom($key);
        else return $program;
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
