<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Tools\Statistic\StatisticProgram;
use Illuminate\Console\Command;

class statisticTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:statistic {model} {id?}';

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
        $id = $this->argument('id') ?? "";
        $model = $this->argument('model') ?? "";
        $statistic = new StatisticProgram();

        //$channels = Channel::where('audit_status', Channel::AUDIT_PASS)->with('programs')->get();
        $channel = Channel::find($id);
        
        // foreach($channels as $channel)
        // {
            $this->info("loading channel {$channel->air_date}");
            $statistic->load($channel);
            $results = $statistic->scan();

            if($results['result']){
                $this->info("统计成功");
                $statistic->store(true);
            }
            else {
                $this->error($results['msg']);
            }

        // }

        return 0;
    }
}
