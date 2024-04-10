<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'plan';

    public const STATUS_EMPTY = 0;
    public const STATUS_RUNNING = 1;
    public const STATUS_EXPIRED = 2;
    public const STATUS_ERROR = 3;

    public const STATUS = ['未启用', '运行中', '已过期', '错误'];

    public const GROUPS = ['xkv'=>'XKV', 'xkc'=>'XKC', 'xki'=>'XKI'];

    protected $fillable = [
        'id',
        'group_id',
        'name',
        'category',
        'start_at',
        'end_at',
        'date_from',
        'date_to',
        'status',
        'type',
        'daysofweek',
        'episodes',
        'is_repeat',
        'data'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'data' => 'array'
    ];

    public function getDaysofweekAttribute($value)
    {
        return explode(',', $value);
    }

    public function setDaysofweekAttribute($value)
    {
        $this->attributes['daysofweek'] = implode(',', $value);
    }

    public static function loadPlan($channel)
    {
        return false;
        $plans = Plan::where('status', Plan::STATUS_RUNNING)->where('group_id', $channel->name)->get();
        $begin = strtotime($channel->air_date.' '.explode(' - ',$channel->start_end)[0]);
        $end = strtotime($channel->air_date.' '.explode(' - ',$channel->start_end)[1]) + 86400;
        $items = [];
        foreach($plans as $plan)
        {
            $planlist = json_decode($plan->data, true);
            if(!$planlist) continue;
            if(!is_array($planlist)) continue;

            foreach($planlist as $line) {
                $start = strtotime($line['start_at']);
                if($start > $begin && $start < $end) {
                    return $line;
                }
            }
        }

        return false;
    }
}
