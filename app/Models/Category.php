<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'category';
    public $timestamps = false;

    public const TYPES = ['tags'=>'标签', 'mood'=>'情绪', 'energy'=>'力量', 'tempo'=>'节奏', 'genre'=>'风格', 'sex'=>'性别'];
    public const CATES = ['movie', 'cartoon', 'drama', 'Entertainm', 'CanXin'];
    public const TYPE_TAGS = 0;
    
    protected $fillable = [
        'id',
        'name',
        'no',
        'type',
        'duration'
    ];

    protected static function booted(): void
    {
        static::deleting(function (Model $model) {
            if(Admin::user()->cannot('delete-category'))
                throw new \Exception('您无权删除栏目标签');
        });
    }

    private static $categories = [];

    public static function getXkcCategories()
    {
        return Record::XKC;
    }

    public static function getFormattedCategories($type='tags', $withEmpty=false)
    {
        if($type == 'all')
            $cates = Category::lazy()->pluck('name', 'no')->toArray();
        else
            $cates = Category::where('type', $type)->lazy()->pluck('name', 'no')->toArray();
        
        if($withEmpty) $list = [''=>'空'];
        else $list = [];

        foreach($cates as $no=>&$c)
        {
            $c = "【{$no}】 $c";
            $list[$no] = $c;
        }

        return $list;
    }

    public static function getCategories($type='tags')
    {
        return Category::where('type', $type)->lazy()->pluck('name', 'no')->toArray();
    }

    public static function findCategory($key, $type='tags')
    {
        if(!key_exists($type, self::$categories)) self::$categories[$type] = self::getCategories($type);
        return array_key_exists($key, self::$categories[$type]) ? 
            self::$categories[$type][$key] : $key;
    }

    public static function parseBg($no, $code='')
    {
        if($no == 'm1') return 'bg-warning';
        if($no == 'v1') return 'bg-default';
        if(preg_match('/VCNM(\w+)/', $code, $m)) return 'bg-info';
        return '';
    }
    
}
