<?php

namespace App\Console\Commands;

use App\Models\ChannelPrograms;
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
        $v = $this->argument('v') ?? "";
        
        $model = Template::find($v);

        DB::transaction(function () use ($model) {
            $model->delete();
        });
        return 0;
    }
}
