<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawFiles extends Model
{
    use HasFactory;

    protected $table = 'raw_files';

    public const STATUS_DEFAULT = 0;
    public const STATUS_READY = 1;
    public const STATUS = ['不可用', '可用',];

    protected $fillable = [
        'id',
        'filename',
        'folder_id',
        'status',
        'name',
        'unique_no'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];
}
