<?php

namespace App\Console\Commands;

use App\Models\Record;
use App\Tools\ChannelGenerator;
use Illuminate\Console\Command;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test {v?}';

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
        $v = $this->argument('v') ?? "";
        
        $n = new ChannelGenerator('xkc');
        $n->makeCopyTemplate();

        //print_r($d);
        return 0;
    }
}
