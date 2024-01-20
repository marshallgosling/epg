<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
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
        'channel',
        'duration',
        'size',
        'frames',
        'group',
        'md5',
        'filepath',
        'comment'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    private static $cache = [];

    protected static function booted(): void
    {
        static::deleting(function (Model $model) {
            if(Admin::user()->cannot('delete-material'))
                throw new \Exception('您无权删除素材库内容');
        });
    }

    public static function findRandom($key)
    {
        if(!Arr::exists(self::$cache, $key)) self::$cache[$key] = self::select('unique_no')->where('category','like',"%$key%")->pluck('unique_no')->toArray();

        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);
        $id = Arr::random(self::$cache[$key]);
        self::$cache[$key] = Arr::shuffle(self::$cache[$key]);

        return self::where('unique_no', $id)
            ->select("unique_no", "name","duration","frames","category")->first();
    }

    public static function findUnique($no)
    {
        return self::where('unique_no', $no)
            ->select("unique_no", "name","duration","frames")->first();
    }
}
