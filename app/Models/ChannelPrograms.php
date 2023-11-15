<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelPrograms extends Model
{
    use HasFactory;

    protected $table = 'channel_program';

    protected $fillable = [
        'id',
        'name',
        'channel_id',
        'schedule_start_at',
        'schedule_end_at',
        'start_at',
        'end_at',
        'duration',
        'version',
        'data'
    ];

    protected $casts = [
        'start_at' => 'datetime:h:i:s',
        'end_at' => 'datetime:h:i:s',
        'created_at' => 'datetime:Y-m-d h:i:s',
        'updated_at' => 'datetime:Y-m-d h:i:s'
    ];
}
