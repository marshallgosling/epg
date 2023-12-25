<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpgJob extends Model
{
    use HasFactory;

    public const STATUS_EMPTY = 0;
    public const STATUS_RUNNING = 1;
    public const STATUS_READY = 2;
    public const STATUS_ERROR = 3;
    public const STATUS_CLOSE = 4;
    public const STATUS_WAITING = 5;


    public const STATUS = ['正常', '错误'];
    public const AUDIT = ['未审核', '通过', '不通过'];

    public const GROUPS = ['xkv'=>'V China', 'xkc'=>'星空中国', 'xki'=>'星空国际'];
    public const DOTS = ['xkv'=>'info','xkc'=>'warning','xki' =>'success'];

    protected $table = 'epg_job';

    protected $fillable = [
        'id',
        'name',
        'status',
        'file',
        'group_id'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
