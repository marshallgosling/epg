<?php

namespace App\Tools\Exporter;

use App\Models\Epg;
use App\Models\Record;
use App\Models\Template;
use App\Models\TemplateRecords;
use Illuminate\Support\Facades\DB;

class TableGenerator
{
    private $indentation = '    ';
    private $xml;
    private $group = 'xkc';

    public function __construct($group='xkc')
    {
        $this->group = $group;
    }

    // TODO: private $this->addtypes = false; // type="string|int|float|array|null|bool"
    public function export($days, $template, $data)
    {
        $table = '<table class="table table-bordered"><tr><th>HKT</th>';
        foreach($days as $day)
        {
            $table .= '<th>'.$day['day'].'<br />'.TemplateRecords::DAYS[$day['dayofweek']].'</th>';
        }
        $table .= '</tr>';

        foreach($template as $t)
        {
            $table .= '<tr><td>'.$t['label_start_at'].'<br>'.$t['label_end_at'].'</td>';
            
            foreach($days as $day) {
                if(!array_key_exists($day['day'], $data))
                {
                    $table .= '<td>&nbsp;</td>';
                    continue;
                }
                $items = $data[$day['day']];
                $table .= '<td>';
                foreach($items as $item) {
                    if($item->schedule_start_at == $t['start_at'])
                        $table .= $item->name.'<br>';
                }
                $table .= '</td>';
            }
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    public function loadTemplate()
    {
        $items = DB::table('template')->where(['group_id'=>$this->group,'schedule'=>Template::DAILY,'status'=>Template::STATUS_SYNCING])->orderBy('sort', 'asc')->get();
        $templates = [];
        $offset = 16;

        for($i=0;$i<3;$i++)
        {
            foreach($items as $item)
            {
                $st = strtotime('2024-01-01 '.$item->start_at) - $offset * 3600;
                $ed = strtotime('2024-01-01 '.$item->end_at) - $offset * 3600;
                $end_at = date('H:i:s', $ed);
                $start_at = date('H:i:s', $st);
                if($start_at == '00:00:00') $start_at = '24:00:00';
                if($end_at == '00:00:00') $end_at = '24:00:00';
                $templates[] = ['start_at'=>$start_at, 'end_at'=>$end_at,
                    'duration'=>$item->duration, 'label_start_at'=>date('G:i', $st), 'label_end_at'=>date('G:i', $ed)];

            }
            $offset -= 8;
        }

        return $templates;
    }

    public function generateDays($st, $ed)
    {
        $days = [];
        for(;$st<=$ed;$st+=86400)
        {
            $days[] = ['day' => date('Y-m-d', $st), 'dayofweek'=>date('N', $st)];
        }
        return $days;
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
        return DB::table('epg')->join('channel_program', 'epg.program_id','=','channel_program.id')
                ->select(['epg.name','epg.program_id','epg.start_at','epg.category','channel_program.schedule_start_at','channel_program.schedule_end_at'])
                ->where('epg.group_id', $this->group)->where('epg.start_at', '>', $start)->where('epg.end_at','<',$end)
                ->whereIn('epg.category', array_keys(Record::XKC))->orderBy('epg.start_at')->get()->toArray();
    }
}