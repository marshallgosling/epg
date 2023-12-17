<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateRecords extends Model
{
    use HasFactory;

    public const TYPE_STATIC = 0;
    public const TYPE_ADVERTISE = 2;
    public const TYPE_RANDOM = 1;
    public const TYPES = ['固定','随机','广告'];
    public const LABELS = ['info', 'warning', 'default'];

    public const DAYS = ['1'=>'周一','2'=>'周二','3'=>'周三','4'=>'周四','5'=>'周五','6'=>'周六','7'=>'周日'];

    public const PROPS = [
        'id',
        'name',
        'category',
        'data',
        'type',
        'template_id',
        'sort'
    ];

    protected $table = 'template_programs';

    protected $fillable = [
        'id',
        'name',
        'category',
        'data',
        'type',
        'template_id',
        'sort'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'data' => 'array'
    ];

    protected static function booted(): void
    {
        static::deleting(function (Model $model) {
            if(Admin::user()->cannot('delete-template'))
                throw new \Exception('您无权删除该模版');
        });
    }

    /*public function getCategoryAttribute($value)
    {
        return explode(',', $value);
    }

    public function setCategoryAttribute($value)
    {
        $this->attributes['category'] = implode(',', $value);
    }*/


    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id', 'id');
    }
}
