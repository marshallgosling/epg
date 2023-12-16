<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    public const STATUS_EMPTY = 0;
    public const STATUS_RUNNING = 1;
    public const STATUS_READY = 2;
    public const STATUS_ERROR = 3;
    public const STATUS_CLOSE = 4;
    public const STATUS_WAITING = 5;

    public const AUDIT_EMPTY = 0;
    public const AUDIT_PASS = 1;
    public const AUDIT_FAIL = 2;

    public const STATUS = ['未编单', '运行中', '正常', '错误', '下线', '等待中'];
    public const AUDIT = ['未审核', '通过', '不通过'];

    public const GROUPS = ['xkv'=>'XKV', 'xkc'=>'XKC', 'xki'=>'XKI'];
    public const DOTS = ['xkv'=>'info','xkc'=>'warning','xki' =>'success'];

    protected $table = 'channel';

    protected $fillable = [
        'id',
        'name',
        'uuid',
        'air_date',
        'status',
        'comment',
        'version',
        'reviewer',
        'audit_status',
        'audit_date',
        'start_end',
        'distribution_date'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function programs()
    {
        return $this->hasMany(ChannelPrograms::class, 'channel_id', 'id');
    }
}
