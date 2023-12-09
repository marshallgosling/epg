<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    public const STATUS_NOUSE = 0;
    public const STATUS_SYNCING = 1;
    public const STATUS_STOPED = 2;

    public const DAILY = 0;
    public const WEEKENDS = 1;
    public const SPECIAL = 2;
    public const SCHEDULES = ["日常", "周末", "特殊"];
    public const STATUSES = ["未启用", "使用中", "已停用"];

    protected $table = 'template';

    protected $fillable = [
        'id',
        'name',
        'schedule',
        'start_at',
        'sort',
        'duration',
        'group_id',
        'status',
        'version',
        'comment'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
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
