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

    public const SCHEDULES = ["日常", "周末"];

    protected $table = 'template';

    protected $fillable = [
        'id',
        'name',
        'schedule',
        'start_at',
        'end_at',
        'duration',
        'group_id',
        'comment'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d h:i:s',
        'updated_at' => 'datetime:Y-m-d h:i:s',
    ];

    public function programs()
    {
        return $this->hasMany(TemplatePrograms::class, 'template_id', 'id');
    }

    public static function getFormattedTemplate($group='default')
    {
        if($group == 'all')
            $cates = Template::lazy()->pluck('name', 'no')->toArray();
        else
            $cates = Template::where('type', $group)->lazy()->pluck('name', 'no')->toArray();
        
        foreach($cates as $no=>&$c)
        {
            $c = "【{$no}】 $c";
        }

        return $cates;
    }

}
