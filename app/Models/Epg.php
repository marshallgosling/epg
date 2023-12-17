<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Epg extends Model
{
    use HasFactory;

    protected $table = 'epg';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'group_id',
        'name',
        'channel_id',
        'start_at',
        'end_at',
        'duration',
        'category',
        'unique_no',
        'program_id',
        'comment'
    ];

    protected $casts = [
        'start_at' => 'datetime:Y-m-d H:i:s',
        'end_at' => 'datetime:Y-m-d H:i:s'
    ];
}
