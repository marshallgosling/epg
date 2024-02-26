<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notification';

    public const TYPE_GENERATE = 0;
    public const TYPE_EXCEL = 1;
    public const TYPE_EPG = 2;
    public const TYPE_AUDIT = 3;
    public const TYPE_STATISTIC = 4;
    public const TYPE_BLACKLIST = 5;
    public const TYPE_XML = 6;
    public const TYPE_MATERIAL = 7;

    public const LEVEL_INFO = 'info';
    public const LEVEL_WARN = 'warning';
    public const LEVEL_ERROR = 'danger';

    public const TYPES = ['generate', 'excel', 'epg', 'audit', 'statistic', 'blacklist', 'xml', 'material'];
    public const LEVELS = ['info'=>'信息', 'warning'=>'警告', 'danger'=>'错误'];

    public const GROUPS = ['_'=>'None','xkv'=>'XKV', 'xkc'=>'XKC', 'xki'=>'XKI'];

    protected $fillable = [
        'id',
        'name',
        'message',
        'type',
        'level',
        'user',
        'viewed',
        'group_id'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
