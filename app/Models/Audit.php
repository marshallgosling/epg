<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $table = 'audit';

    public const STATUS_WAIT = 0;
    public const STATUS_PASS = 1;
    public const STATUS_FAIL = 2;
    public const STATUS = ['未审核', '通过', '不通过'];

    protected $fillable = [
        'id',
        'name',
        'reason',
        'status',
        'channel_id',
        'admin'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }
}
