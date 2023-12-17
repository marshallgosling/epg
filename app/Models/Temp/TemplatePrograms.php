<?php

namespace App\Models\Temp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplatePrograms extends Model
{
    use HasFactory;

    public const TYPE_PROGRAM = 0;
    public const TYPE_ADVERTISE = 1;
    public const TYPE_STATIC = 2;
    public const TYPES = ['节目','广告','固定'];
    public const LABELS = ['info', 'warning', 'default'];

    protected $table = 'temp_template_programs';

    protected $fillable = [
        'id',
        'name',
        'category',
        'data',
        'type',
        'template_id',
        'sort'
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
