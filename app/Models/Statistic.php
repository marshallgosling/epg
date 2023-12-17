<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    use HasFactory;

    protected $table = 'statistic';

    public const TYPE_DAILY = 0;
    public const TYPE_COUNT = 1;
    public const TYPE_SPECIAL = 2;
    public const TYPES = ["每日", "累计", "特殊"];
    public const MODELS = ['Program'];
    public const GROUPS = ['xkv'=>'XKV', 'xkc'=>'XKC', 'xki'=>'XKI'];

    protected $fillable = [
        'id',
        'model',
        'column',
        'value',
        'type',
        'group',
        'category',
        'date'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Model $model) {
            if(Admin::user()->cannot('delete-statistic'))
                throw new \Exception('您无权删除');
        });
    }
}
