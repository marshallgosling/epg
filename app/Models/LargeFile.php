<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LargeFile extends Model
{
    use HasFactory;

    protected $table = 'large_file';

    public const STATUS_EMPTY = 0;
    public const STATUS_READY = 1;
    public const STATUS_ERROR = 2;
    public const STATUS = ['待处理', '已处理', '错误'];

    protected $fillable = [
        'id',
        'name',
        'path',
        'status',
        'target_path',
        'storage'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
