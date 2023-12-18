<?php

namespace App\Console\Commands;

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
        
        $start_at = strtotime($air_date.' 06:00:00');
        $pos_start = (int)Epg::where('start_at','>',$start_at-300)->where('start_at','<',$start_at+300)->orderBy('start_at', 'desc')->limit(1)->value('id')->dd();
        $start_at += 86400;
        $pos_end = (int)Epg::where('start_at','>',$start_at-300)->where('start_at','<',$start_at+300)->orderBy('start_at', 'desc')->limit(1)->value('id');

        $this->info("$pos_start - $pos_end");

        return 0;
    }
}
