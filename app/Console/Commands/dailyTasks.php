<?php

namespace App\Console\Commands;

use App\Jobs\Material\MediaInfoJob;
use App\Jobs\Material\ScanFolderJob;
use App\Models\Channel;
use App\Models\Folder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Tools\Exporter\ZipperFiles;

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
        $args = $this->argument('args') ?? false;

        if(in_array($action, ['xml', 'backup', 'scan']))
            $this->$action($args);
        
        return 0;
    }

    private function xml($args)
    {
        $now = $args ? strtotime($args) : (time() + 7 * 86400);

        $list = Channel::where('status', Channel::STATUS_READY)
                ->where('lock_status', Channel::LOCK_ENABLE)
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

    private function backup($args)
    {
        $d = (int)date('d');
        if($d != 1) return;
        $begin = $args ?? date('Y-m-d');
        $start = Carbon::parse($begin)->addMonths(-2);

        $list = [];
        for($i=0;$i<31;$i++)
        {
            $day = $start->format('Y-m-d');
            foreach(Channel::GROUPS as $ch=>$f)
            {
                $name = $ch.'_'.$day.'.xml';
                if(Storage::disk('xml')->exists($name)) $list[] = $name;
            }

            if($start->isLastDay()) break;
            $start = $start->addDay();
        }

        if(count($list))
        {
            $ret = ZipperFiles::createZip($start->format('Y-MM-DD').'.zip', $list);
            if($ret)
            {
                foreach($list as $file)
                {
                    Storage::disk('xml')->delete($file);
                }
            }
        }
    }

    private function scan($args)
    {
        for($i=2;$i<8;$i++)
        {
            ScanFolderJob::dispatch($i, 'scanandimport')->onQueue('media');
        }
    }
}
