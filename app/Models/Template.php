<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    public const STATUS_READY = 0;
    public const STATUS_SYNCING = 1;
    public const STATUS_STOPED = 2;

    protected $table = 'template';

    protected $fillable = [
        'id',
        'name',
        'type',
        'start_at',
        'end_at',
        'status',
        'summary'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d h:i:s',
        'updated_at' => 'datetime:Y-m-d h:i:s',
    ];

    public function programs()
    {
        return $this->hasMany(TemplatePrograms::class, 'template_id', 'id');
    }
}
