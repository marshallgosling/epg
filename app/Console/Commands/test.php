<?php

namespace App\Console\Commands;

use App\Models\ChannelPrograms;
use App\Models\Record;
use App\Tools\ChannelGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
        
        $p = ChannelPrograms::find($v);
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

        $p->data = json_encode($list);
        $p->save();
        return 0;
    }
}
