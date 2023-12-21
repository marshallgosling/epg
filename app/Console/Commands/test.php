<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\TemplateRecords;
use App\Models\Epg;
use App\Models\Record;
use App\Models\Template;
use App\Tools\ChannelGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test {v?} {d?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $group = $this->argument('v') ?? "";
        $day = $this->argument('d') ?? "2024-02-06";
        
        $day = strtotime($day);

        $templates = Template::with('records')->where(['group_id'=>$group,'schedule'=>Template::DAILY,'status'=>Template::STATUS_SYNCING])->orderBy('sort', 'asc')->get();
        
        for($i=0;$i<20;$i++)
        {
            $channel = new Channel();
            $channel->id = $i;
            $channel->name = $group;
            $channel->air_date = date('Y-m-d', $day);
            $day += 86400;

            $air = 0;
            $duration = 0;
            $epglist = [];
            
            foreach($templates as $template)
            {
                if($air == 0) $air = strtotime($channel->air_date.' '.$template->start_at);  
                $epglist = []; 
                // This is one single Program
                $program = ChannelGenerator::createChannelProgram($template);

                $program->channel_id = $channel->id;
                $program->start_at = date('Y-m-d H:i:s', $air);

                $template_items = $template->records;

                $template_item = $this->findAvailableTemplateItem($channel, $template_items);

                if(!$template_item) {
                    $this->info("没有找到匹配的模版数据: {$template->id} {$template->category}");
                    continue;
                }

                $this->info(json_encode($template_item->data, JSON_UNESCAPED_UNICODE));

                $maxDuration = ChannelGenerator::parseDuration($template->duration); + (int)config('MAX_DURATION_GAP', 600);
                $items = $this->findAvailableRecords($template_item, $maxDuration);

                if(count($items)) {
                    foreach($items as $item) {
                        $seconds = ChannelGenerator::parseDuration($item->duration);
                        if($seconds > 0) {
                            
                            $duration += $seconds;
                            
                            $line = ChannelGenerator::createItem($item, $template_item->category, date('H:i:s', $air));
                            
                            $air += $seconds;

                            $line['end_at'] = date('H:i:s', $air);

                            $epglist[] = $line;
                                
                            $this->info("添加节目: {$template_item->category} {$item->name} {$item->duration}");



                        }
                        else {

                            $this->warn(" {$item->name} 的时长为 0 （{$item->duration}）, 因此忽略.");
                            //throw new GenerationException("{$item->name} 的时长为 0 （{$item->duration}）", Notification::TYPE_GENERATE);
                        }
                    }
                    if(count($epglist) == 0) {
                        $this->error(" 异常1，没有匹配到任何节目  {$template_item->id} {$template_item->category}");
                    }
                }
                else {
                    $this->error(" 异常2，没有匹配到任何节目  {$template_item->id} {$template_item->category}");
                }

                $program->duration = $duration;
                $program->data = json_encode($epglist);
                $program->end_at = date('Y-m-d H:i:s', $air);
            }
        }

        return 0;
    }

    private function findAvailableRecords(TemplateRecords &$template, $maxDuration)
    {
        $items = [];
        if($template->type == TemplateRecords::TYPE_RANDOM) {
            $temps = Record::findNextAvaiable($template, $maxDuration);
            if(in_array($temps[0], ['finished', 'empty'])) {
                $d = $template->data;
                $d['episodes'] = null;
                $d['unique_no'] = '';
                $d['name'] = '';
                $d['result'] = '';
                $template->data = $d;

                $temps = Record::findNextAvaiable($template, $maxDuration);
            }

            foreach($temps as $item) {
                if(!in_array($item, ['finished', 'empty'])) {
                    $items[] = $item;
                }
            }
            
        }
        else if($template->type == TemplateRecords::TYPE_STATIC) {
                
            $temps = Record::findNextAvaiable($template, $maxDuration);
            $items = [];

            if(in_array($temps[0], ['finished', 'empty'])) return $items;
            
            $d = $template->data;
            foreach($temps as $item) {
                if($item == 'empty') {
                    $d['result'] = '未找到';
                }
                else if($item == 'finished') {
                    $d['result'] = '编排完';
                }
                else {
                    $items[] = $item;
                    $d['episodes'] = $item->episodes;
                    $d['unique_no'] = $item->unique_no;
                    $d['name'] = $item->name;
                    $d['result'] = '编排中';
                }
                $template->data = $d;
                //$p->save();
            }
        }

        return $items;
    }

    private function findAvailableTemplateItem($channel, $templateItems)
    {
        $air = strtotime($channel->air_date);
        $dayofweek = date('N', $air);

        foreach($templateItems as $p)
        {
            if(!in_array($dayofweek, $p->data['dayofweek'])) continue;
            $begin = $p->data['date_from'] ? strtotime($p->data['date_from']) : 0;
            $end = $p->data['date_to'] ? strtotime($p->data['date_to']) : 999999999999;
            if($air < $begin || $air > $end) {
                $lasterror = "{$p->id} {$p->category} 编排设定时间 {$p->data['date_from']}/{$p->data['date_to']} 已过期";
                continue;
            }

            if($p->data['result'] == '编排完') continue;

            return $p;
        }

        return false;
    }
    
}
