<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlackList extends Model
{
    use HasFactory;
    public const STATUS_INIT = 0;
    public const STATUS_RUNNING = 1;
    public const STATUS_READY = 2;
    public const STATUS_CLOSE = 3;

    public const STATUS = ['未扫描', '扫描中', '生效中'];
    public const GROUPS = ['artist'=>'艺人','name'=>"标题",'unique_no'=>'播出编号'];

    protected $table = 'blacklist';
    
    protected $fillable = [
        'id',
        'keyword',
        'group',
        'status',
        'data',
        'scaned_at'
    ];

    protected $casts = [
        'scaned_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];
}
