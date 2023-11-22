<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
class Material extends Model
{
    use HasFactory;
    
    protected $table = 'material';

    protected $fillable = [
        'id',
        'name',
        'unique_no',
        'category',
        'duration',
        'size',
        'frames'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    private static $cache = [];

    public static function findRandom($key)
    {
        if(!Arr::exists(self::$cache, $key)) self::$cache[$key] = self::select('unique_no')->where('category','like',"%$key%")->pluck('unique_no')->toArray();

        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);
        $id = Arr::random(self::$cache[$key]);
        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);

        return self::where('unique_no', $id)
            ->select("name","duration","frames","category","unique_no")->first();
    }

    public static function findUnique($no)
    {
        return self::where('unique_no', $no)
            ->select("name","duration","frames","category","unique_no")->first();
    }
}
