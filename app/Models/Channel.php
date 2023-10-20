<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    public const STATUS = ['正常', '下线'];
    public const AUDIT = ['未审核', '通过', '不通过'];

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
        'distribution_date'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d h:i:s',
        'updated_at' => 'datetime:Y-m-d h:i:s',
    ];
}
