<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplatePrograms extends Model
{
    use HasFactory;

    public const STATUS_READY = 0;
    public const STATUS_SYNCING = 1;
    public const STATUS_STOPED = 2;
    public const TYPES = ['节目','广告','垫片'];
    public const LABELS = ['info', 'warning', 'default'];

    protected $table = 'template_programs';

    protected $fillable = [
        'id',
        'name',
        'category',
        'data',
        'type',
        'template_id',
        'order_no',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        //'data' => 'array'
    ];

    /*public function getCategoryAttribute($value)
    {
        return explode(',', $value);
    }

    public function setCategoryAttribute($value)
    {
        $this->attributes['category'] = implode(',', $value);
    }*/

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id', 'id');
    }
}
