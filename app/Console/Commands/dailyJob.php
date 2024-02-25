<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class dailyJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:job {action?} {args?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily jobs';

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

    }

    private function check($args)
    {

    }

    private function scan($args)
    {

    }
}
