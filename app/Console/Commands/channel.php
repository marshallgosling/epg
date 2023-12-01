<?php

namespace App\Console\Commands;

use App\Events\Channel\CalculationEvent;
use App\Models\Category;
use App\Models\ChannelPrograms;
use App\Models\Channel as Modal;
use App\Tools\ChannelGenerator;
use App\Tools\Exporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class channel extends Command
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
        $group = $this->argument('group') ?? "default";
        $id = $this->argument('id') ?? "";
        $action = $this->argument('action') ?? "";

        if($action == 'list') $this->generateChannel($id, $group);

        if($action == 'export') $this->exportChannel($id);

        if($action == 'calculate') $this->calculateChannel($id, $group);

        return 0;
    }

    private function calculateChannel($id, $pid)
    {
        $this->info("start calcution: $id $pid");
        CalculationEvent::dispatch($id, $pid);
    }

    private function exportChannel($id)
    {
        Exporter::generate($id);
        Exporter::exportXml(true);
    }

    private function generateChannel($id, $group='default')
    {
    
        $channel = Modal::where('id', $id)->first();

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

        $channel->status = Modal::STATUS_READY;
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
