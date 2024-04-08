<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expiration extends Model
{
    use HasFactory;

    protected $table = 'expiration';

    public const STATUS_EMPTY = 0;
    public const STATUS_READY = 1;

    public const STATUS = ['未启用', '已启用'];

    protected $fillable = [
        'id',
        'name',
        'agreement_id',
        'start_at',
        'end_at',
        'status',
        'comment'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'start_at' => 'datetime:Y-m-d',
        'end_at' => 'datetime:Y-m-d'
    ];

    public function agreement()
    {
        return $this->belongsTo(Agreement::class, 'agreement_id', 'id');
    }
}
