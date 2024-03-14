<?php

namespace App\Console\Commands;

use App\Jobs\Material\MediaInfoJob;
use App\Models\Channel;
use Illuminate\Console\Command;

class dailyTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:tasks {action?} {args?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily tasks, action:[xml]';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action') ?? "xml";
        $args = $this->argument('args') ?? "";

        if(in_array($action, ['xml', 'check', 'scan']))
            $this->$action($args);
        
        return 0;
    }

    private function xml($args)
    {
        $now = $args ? strtotime($args) : (time() + 7 * 86400);

        $list = Channel::where('status', Channel::STATUS_READY)
                ->where('audit_status', Channel::LOCK_ENABLE)
                ->where('distribution_date', null)
                ->where('air_date', date('Y-m-d', $now))
                //->orderBy('air_date')
                ->get();

        if(!$list) return;
        foreach($list as $model)
        {
            MediaInfoJob::dispatch($model->id, 'distribute')->onQueue('media');
        }
        
    }

    private function check($args)
    {

    }

    private function scan($args)
    {

    }
}
