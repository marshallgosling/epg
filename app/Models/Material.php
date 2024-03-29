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

    public const STATUS_EMPTY = 0;
    public const STATUS_READY = 1;
    public const STATUS_ERROR = 2;
    public const STATUS_PRO = 3;
    public const STATUS = ['不可用', '可用', '错误'];

    protected $fillable = [
        'id',
        'name',
        'name2',
        'ep',
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
        if(!Arr::exists(self::$cache, $key)) self::$cache[$key] = self::select('unique_no')->where('category','like',"%$key%")->where('status', Material::STATUS_READY)->pluck('unique_no')->toArray();

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

    public static function getFileName($no)
    {
        $filepath = self::where('unique_no', $no)->value('filepath');
        if($filepath)
        {
            $filename = explode('\\', $filepath);
            $filename = array_pop($filename);
        
            return str_replace('.mxf', '', $filename);
        }
        return false;
    }

    public static function getName($no)
    {
        $name = self::where('unique_no', $no)->value('name');
        if($name)
        {
            return $name.'.'.$no;
        }
        return false;
    }
}
