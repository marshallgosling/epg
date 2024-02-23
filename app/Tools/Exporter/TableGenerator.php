<?php

namespace App\Tools\Exporter;

use App\Models\Epg;
use App\Models\Record;
use Illuminate\Support\Facades\DB;

class TableGenerator
{
    private $indentation = '    ';
    private $xml;
    private $group = 'xkc';
    // TODO: private $this->addtypes = false; // type="string|int|float|array|null|bool"
    public function export($days, $template, $data)
    {
        $table = '<table class="table table-bordered"><th><td>HKT</td>';
        foreach($days as $day)
        {
            $table .= '<td>'.$day['day'].'<br />'.$day['dayofweek'].'</td>';
        }
        $table .= '</th>';

        foreach($template as $t)
        {
            $table .= '<tr><td>'.$t['start_at'].'<br>'.$t['end_at'].'</td>';
            
            foreach($days as $day){
                if(!array_key_exists($day['day'], $data))
                {
                    $table .= '<td>&nbsp;</td>';
                    continue;
                }
                $items = $data[$day['day']];
                $table .= '<td>';
                foreach($items as $item) {
                    if($item['schedule_start_at'] == $t['start_at'])
                        $table .= $item['name'].'<br>';
                }
                $table .= '</td>';
            }
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    public function generateDays($month)
    {
        $day = date('Y').'-'.$month.'-01';
        $stamp = strtotime($day);
        $dayofweek = date('N', $stamp); // 1 - 7
    }

    public function processData($days)
    {
        $data = [];
        foreach($days as $day)
        {
            $start = $day['day'].' 00:59:00';
            $end = strtotime($start) + 86400;
            $data[$day['day']] = $this->collectData($start, date('Y-m-d H:i:s', $end));
        }
        return $data;
    }

    public function collectData($start, $end)
    {
        $items = DB::table('epg')->join('channel_program', 'epg.program_id','=','channel_program.id')
                ->select(['epg.name','epg.program_id','epg.start_at','epg.category','channel_program.schedule_start_at','channel_program.schedule_end_at'])
                ->where('epg.group_id', $this->group)->where('epg.start_at', '>', $start)->where('epg.end_at','<',$end)
                ->where('epg.category', 'in', array_keys(Record::XKC))->orderBy('epg.start_at')->get();
        return $items->toArray();
    }
}