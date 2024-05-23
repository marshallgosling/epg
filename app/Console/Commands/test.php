<?php

namespace App\Console\Commands;

use App\Events\Channel\CalculationEvent;
use App\Jobs\ExcelJob;
use App\Jobs\Material\ScanFolderJob;
use App\Models\Agreement;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\TemplateRecords;
use App\Models\Epg;
use App\Models\Expiration;
use App\Models\Keywords;
use App\Models\Material;
use App\Models\Record;
use App\Models\Record2;
use App\Models\Template;
use App\Tools\ChannelGenerator;
use App\Tools\Exporter\BvtExporter;
use App\Tools\Exporter\ExcelWriter;
use App\Tools\Exporter\TableGenerator;
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
        $list = DB::table('agreement')->get();
        foreach($list as $agreement)
        {
            $episodes = DB::table('expiration')->where('agreement_id', $agreement->id)->get();
            foreach($episodes as $exp)
            {
                Record::where('episodes', $exp->name)->update(['air_date'=>$agreement->start_at, 'expired_date'=>$agreement->end_at]);
                $this->info("update records expiration date : {$exp->name} {$agreement->name} {$agreement->start_at} - {$agreement->end_at}");
            }
        }

       
        return;
        
        $list = ChannelPrograms::where('channel_id', $group)->get();
        foreach($list as $p)
        {
            $data = json_decode($p->data);
            if(key_exists('replicate', $data)) continue;
            
            foreach($data as &$item)
            {
                if(is_array($item->category))
                {
                    //$category = Record2::where('unique_no', $item->unique_no)->value('category');
                    $item->category = $item->category[0];
                }
            }
            $p->data = json_encode($data);
            $p->save();
        }

        return;
        
        
        $channel = Channel::where('name', $group)->where('air_date', $day)->first();
        
        $programs = $channel->programs()->get();
        $relations = [];
        foreach($programs as $pro)
        {
            if(strpos($pro->name, '(副本)')) {
                $name = str_replace(' (副本)','',$pro->name);
                $pro->data = '{"replicate":'.$relations[$name].'}';
                $this->info("get relation: {$pro->name} => {$relations[$name]}");
                $pro->save();
            }
            else {
                $relations[$pro->name] = $pro->id;
                $this->info("setup relation: {$pro->name} => {$pro->id}");
            }
        }

        CalculationEvent::dispatch($channel->id);

        return 0;


        $list = Material::where('filepath', 'like', '%卡通%')->get();

        foreach($list as $line)
        {
            $info = explode('\\', $line->filepath);
            $line->filepath = 'Y:\\卡通\\'.array_pop($info);
            $line->save();
            $this->info($line->filepath);
        }

        return 0;


        $data = $this->getRawData();
        foreach($data as $line)
        {
            $items = explode("\t", $line);

            $m = Material::where('name', $items[0])->first();
            if($m)
            {
                $m->comment = trim($items[2]);
                $m->save();
            }
        }

        $data = $this->getRawTitle();
        foreach($data as $line)
        {
            $items = explode("\t", trim($line));

            if(count($items)==2)
                DB::table('material')->where('group', trim($items[1]))->update(['comment'=>trim($items[0])]);
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
            $d = $template->data;
            foreach($temps as $item) {
                if(!in_array($item, ['finished', 'empty'])) {
                    $items[] = $item;
                    $d['episodes'] = $item->episodes;
                    $d['unique_no'] = $item->unique_no;
                    $d['name'] = $item->name;
                    $d['result'] = '编排中';
                    $template->data = $d;
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

        $this->info("dayofweek: ".$dayofweek);

        foreach($templateItems as &$p)
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