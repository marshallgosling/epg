<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Program extends Model
{
    use HasFactory;

    protected $table = 'program';

    protected $fillable = [
        'id', 'name', 'unique_no','category', 'comment',
        'album','artist','co_artist', 'duration',
        'company', 'air_date', 'product_date', 
        'genre', 'gender','lang','mood','tempo','energy'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d h:i:s',
        'updated_at' => 'datetime:Y-m-d h:i:s',
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

    public static function findRandom($key)
    {
        if(!Arr::exists(self::$cache, $key)) self::$cache[$key] = self::select('unique_no')->where('category','like',"%$key%")->pluck('unique_no')->toArray();

        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);
        $id = Arr::random(self::$cache[$key]);
        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);

        return Program::where('unique_no', $id)
            ->join('material', 'program.unique_no', '=', 'material.unique_no')
            ->select("program.name","material.duration","material.frames","program.category","program.unique_no")->first();
    }

    public static function getTotal($key) {
        return Arr::exists(self::$cache, $key) ? count(self::$cache[$key]) : 0;
    }
}
