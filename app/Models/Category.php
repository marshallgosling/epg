<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'category';
    public $timestamps = false;

    public const TYPES = ['channel'=>'Channel', 'mood'=>'Mood', 'energy'=>'Energy', 'tempo'=>'Tempo', 'genre'=>'SongStyle', 'sex'=>'Sex'];

    protected $fillable = [
        'id',
        'name',
        'no',
        'type',
        'duration'
    ];

    private static $categories = [];

    public static function getFormattedCategories($type='channel', $withEmpty=false)
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

    public static function getCategories($type='channel')
    {
        return Category::where('type', $type)->lazy()->pluck('name', 'no')->toArray();
    }

    public static function findCategory($key, $type='channel')
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
