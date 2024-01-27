<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public const GROUPS = ['xkv'=>'V China', 'xkc'=>'星空中国', 'xki'=>'星空国际'];
    public const DOTS = ['xkv'=>'info','xkc'=>'warning','xki' =>'success'];
    public const CLASSES = ['xkv'=>Program::class, 'xkc'=>Record::class, 'xki'=>Record2::class];

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

    protected static function booted(): void
    {
        static::deleting(function (Model $model) {
            if(Admin::user()->cannot('delete-channel'))
                throw new \Exception('您无权删除该串联单');
        });
    }

    public function programs()
    {
        return $this->hasMany(ChannelPrograms::class, 'channel_id', 'id');
    }

    public static function generate($group, $start, $end)
    {
        $list = [];
        for(;$start<$end;$start+=86400) {
            
            if(Channel::where('air_date', date('Y-m-d', $start))->where('name', $group)->exists())
            {
                continue;
            }

            $ch = new Channel();

            $ch->name = $group;
            $ch->air_date = date('Y-m-d', $start);
            $ch->uuid = (string) Str::uuid();
            $ch->version = 1;
            $ch->status = Channel::STATUS_EMPTY;
            $ch->audit_status = Channel::AUDIT_EMPTY;
            $ch->save();
            $list[] = $ch;
        }
        return array_reverse($list);
    }
}
