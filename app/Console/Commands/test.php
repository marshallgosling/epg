<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelPrograms;
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
        $air_date = $this->argument('v') ?? "";
        $start_at = '2024-01-02';
        $end_at = '2024-01-11';

        $channels = Channel::where(['status'=>Channel::STATUS_EMPTY,'name'=>'xkc'])
                    ->where('air_date','>=',$start_at)->where('air_date','<=',$end_at)->get();

        print_r($channels->toArray());
        // $start_at = strtotime($air_date.' 06:00:00');
        // $pos_start = (int)Epg::where('start_at','>',$air_date.' 05:58:00')->where('start_at','<',$air_date.' 06:04:00')->orderBy('start_at', 'desc')->limit(1)->value('id');
        // $start_at += 86400;
        // $air_date = date('Y-m-d', $start_at);
        // $pos_end = (int)Epg::where('start_at','>',$air_date.' 05:58:00')->where('start_at','<',$air_date.' 06:04:00')->orderBy('start_at', 'desc')->limit(1)->value('id');

        //$this->info("$pos_start - $pos_end");

        return 0;
    }
}
