<?php

namespace App\Console\Commands;

use App\Events\Channel\CalculationEvent;
use App\Models\Category;
use App\Models\ChannelPrograms;
use App\Models\Channel;
use App\Tools\ChannelGenerator;
use App\Tools\Exporter;
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

        $actions = ['generate', 'create', 'export', 'calculate'];

        if(!in_array($action, $actions)) {
            $this->error("action param's value only supports ".implode(',', $actions));
            return 0;
        }

        $this->$action($id, $group);

        return 0;
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
        Exporter::generate($id);
        Exporter::exportXml(true);
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

        $generator = new ChannelGenerator();
        $generator->loadTemplate($channel->name);

        $generator->generate($channel);

        $channel->status = Channel::STATUS_READY;
        $channel->save();

        $this->info("Generate programs date: {$channel->air_date} succeed. ");
        
    }

    private function parseDuration($str)
    {
        $duration = explode(':', $str);
        
        $seconds = count($duration )>= 3 ? (int)$duration[0]*3600 + (int)$duration[1]*60 + (int)$duration[2] : 0;

        return $seconds;
    }
}
