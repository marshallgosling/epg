<?php

namespace App\Models;

use Encore\Admin\Grid\Filter\Group;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportList extends Model
{
    use HasFactory;

    protected $table = 'export_job';

    public const STATUS_EMPTY = 0;
    public const STATUS_RUNNING = 1;
    public const STATUS_READY = 2;
    public const STATUS_ERROR = 3;

    public const TYPE_NORMAL = 0;
    public const TYPE_HK = 1;

    public const STATUS = ['未生成', '运行中', '可下载', '错误'];

    public const GROUPS = ['xkv'=>'XKV', 'xkc'=>'XKC', 'xki'=>'XKI'];
    public const TYPES = ['普通','香港'];

    protected $fillable = [
        'id',
        'name',
        'start_at',
        'end_at',
        'type',
        'filename',
        'status',
        'reason',
        'group_id'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
