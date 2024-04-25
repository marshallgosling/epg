<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keywords extends Model
{
    use HasFactory;
    public const STATUS_INIT = 0;
    public const STATUS_RUNNING = 1;
    public const STATUS_READY = 3;
    public const STATUS_SCANNED = 2;

    public const STATUS = ['未扫描', '扫描中', '扫描完成', '生效中'];
    public const CATES = ['movie'=>'精确','other'=>"模糊"];

    protected $table = 'keywords';
    
    protected $fillable = [
        'id',
        'keyword',
        'category',
        'status',
        'value',
        'language'
    ];

    protected $casts = [
        'scaned_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];
}
