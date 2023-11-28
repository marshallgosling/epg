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
        'sort',
        'data'
    ];

    protected $casts = [
        'start_at' => 'datetime:H:i:s',
        'end_at' => 'datetime:H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];



    public function exportXML()
    {
        $data = json_decode($this->data, true);
        $start = strtotime($this->start_at);
        foreach($data as &$item)
        {
            $duration = explode(':', $item['duration']);
            $seconds = (int)$duration[0]*3600 + (int)$duration[1]*60 + (int)$duration[2];

            $item['start_at'] = date('H:i:s', $start);
            $item['end_at'] = date('H:i:s', $start+$seconds);

            $start += $seconds;
        }


    }

    /**
     * caculate time format H:i:s to seconds
     * @param  string  $time 
     * @return int seconds
     */
    public static function caculateSeconds($time)
    {
        $duration = explode(':', $time);

        return count($duration) > 2 ? (int)$duration[0]*3600 + (int)$duration[1]*60 + (int)$duration[2] : 0;
    }

    /**
     * caculate time format H:i:s to frames
     * @param  string  $time 
     * @return int frames
     */
    public static function caculateFrames($time)
    {
        return self::caculateSeconds($time) * config('FRAME', 25);
    }
}
