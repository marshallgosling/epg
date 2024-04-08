<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;
    protected $table = 'folder';

    public const STATUS_DEFAULT = 0;
    public const STATUS_READY = 1;
    public const STATUS_SCAN = 2;
    public const STATUS_ERROR = 3;
    public const STATUS = ['未扫描', '扫描完成', '扫描中','扫描出错'];

    protected $fillable = [
        'id',
        'path',
        'data',
        'status',
        'comment',
        'scaned_at'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'scaned_at'  => 'datetime:Y-m-d H:i:s',
        //'data'  => 'json'
    ];

    public function rawfiles()
    {
        return $this->hasMany(RawFiles::class, 'folder_id', 'id');
    }
}
