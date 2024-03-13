<?php

namespace App\Console\Commands;

use App\Events\Channel\CalculationEvent;
use App\Models\Category;
use App\Models\ChannelPrograms;
use App\Models\Channel;
use App\Models\Notification;
use App\Tools\ChannelDatabase;
use App\Tools\ChannelGenerator;
use App\Tools\Exporter\BvtExporter;
use App\Tools\Generator\GenerationException;
use App\Tools\Generator\XkcGenerator;
use App\Tools\Generator\XkvGenerator;
use App\Tools\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class channelTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:channel {action?} {id?} {group?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create channel programs using template.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $group = $this->argument('group') ?? "xkv";
        $id = $this->argument('id') ?? "";
        $action = $this->argument('action') ?? "";

        $actions = ['generate', 'create', 'export', 'calculate', 'fixer','epg'];

        if(!in_array($action, $actions)) {
            $this->error("action param's value only supports ".implode(',', $actions));
            return 0;
        }

        $this->$action($id, $group);

        return 0;
    }

    private function epg($id)
    {
        
        $channels = Channel::where('name', $id)->where('status', Channel::STATUS_READY)->orderBy('air_date')->get();

        foreach($channels as $channel) {
            ChannelDatabase::saveEpgToDatabase($channel);
        }
        //ChannelDatabase::removeEpg($channel);
        
    }

    private function fixer($id)
    {
        $programs = ChannelPrograms::where('channel_id', $id)->get();

        foreach($programs as $p) {

            $data = json_decode($p->data, true);
            $list = [];
            for($i=0;$i<100;$i++)
            {
                if(array_key_exists($i, $data))
                {
                    $list[] = $data[$i];
                }
                else {
                    break;
                }
            }

            if(array_key_exists('replicate', $data)) continue;
            $p->data = json_encode($list);
            $p->save();
        }
        
        
        // $channels = Channel::where('status', Channel::STATUS_READY)->where('name', $id)->get();

        // foreach($channels as $channel)
        // {
        //     $pros = ChannelPrograms::where('channel_id', $channel->id)->orderBy('id')->get()->toArray();
        //     if($pros)
        //     {
                
        //         $start_end = $pros[0]['start_at']. ' - '.$pros[count($pros) - 1]['end_at'];
        //         $channel->start_end = $start_end;
        //         $channel->save();
        //     }
        // }
    }

    private function create($id, $group)
    {
        $start = explode(':',$id)[0];
        $days = (int)explode(':',$id)[1];

        $start = strtotime($start. " 06:00:00");
        for($i=0;$i<$days;$i++)
        {
            if(Channel::where('air_date', date('Y-m-d', $start))->where('name', $group)->exists()) {
                $start += 3600*24;
                continue;
            }
            $channel = new Channel();
            $channel->name = $group;
            $channel->uuid = (string) Str::uuid();
            $channel->air_date = date('Y-m-d', $start);
            $channel->version = 1;

            $channel->save();

            $start += 3600*24;

            $this->info("Create channel {$channel->air_date} success.");
        }
    }

    private function calculate($id, $pid)
    {
        $this->info("start calcution: $id $pid");
        CalculationEvent::dispatch($id, $pid);
    }

    private function export($id, $pid)
    {
        $channel = Channel::findOrFail($id);

        $data = BvtExporter::collectData($channel->air_date, $channel->name);

        BvtExporter::generateData($channel, $data);
        print_r($data);
        BvtExporter::exportXml($channel->name);
    }

    private function generate($id, $group='default')
    {
    
        $channel = Channel::where('id', $id)->first();

        if(!$channel) {
            $this->error("Channel is null.");
            return 0;
        }

        if(ChannelPrograms::where('channel_id', $channel->id)->exists()) {
            $this->error("Programs exist.");
            return 0;
        }

        // $generator = new ChannelGenerator($channel->name);
        // $generator->makeCopyTemplate();
        // $generator->loadTemplate();

        if($channel->name == 'xkv') $generator = new XkvGenerator($channel->name);
        else $generator = new XkcGenerator($channel->name);

        $generator->loadTemplate();
        
        try {
            $start_end = $generator->generate($channel);

        }catch(GenerationException $e)
        {
            $this->error($e->getMessage());
            Notify::fireNotify(
                $channel->name,
                Notification::TYPE_GENERATE, 
                "生成节目编单 {$channel->name}_{$channel->air_date} 数据失败. ", 
                "详细错误:".$e->getMessage(), 'error'
            );
            $channel->start_end = '';
            $channel->status = Channel::STATUS_ERROR;
            $channel->save();
            return;
        }
        
        //$generator->saveTemplateState();

            $channel->start_end = $start_end;
            $channel->status = Channel::STATUS_READY;
            $channel->save();

        $this->info("Generate programs date: {$channel->air_date} succeed. ");
        
        Notify::fireNotify(
            $channel->name,
            Notification::TYPE_GENERATE, 
            "生成节目编单 {$channel->name}_{$channel->air_date} 数据成功. ", 
            "频道节目时间 $start_end"
        );

        //$generator->cleanTempData();

    }

}
