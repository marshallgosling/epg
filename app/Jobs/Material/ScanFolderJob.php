<?php

namespace App\Jobs\Material;

use App\Models\Folder;
use App\Tools\Material\RecognizeFileInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ScanFolderJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Job ID;
    private $id;

    // Action
    private $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $action)
    {
        $this->id = $id;
        $this->action = $action;
        // $this->log_channel = 'black';
        // $this->log_print = false;
    }

    public function uniqueId()
    {
        return $this->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $model = Folder::find($this->id);
        if(!$model) return;
        
        $action = $this->action;

        if(in_array($action, ['scan', 'apply']))
        {
            $this->$action($model);
        }   
        else
        {
            $model->status = Folder::STATUS_ERROR;
            $model->save();
        }
        
    }

    public function scan($folder)
    {
        $d = $folder->path;
        if(!$d) {
            //$folder->data = [];
            $folder->status = Folder::STATUS_ERROR;
            $folder->scaned_at = date('Y-m-d H:i:s');
            $folder->save();
            return;
        }

        $list = [];
        while (($file = $d->read()) !== false){
            if($file != '.' && $file != '..') {
                $m = RecognizeFileInfo::recognize($file);
                if($m) $list[] = $m;
            }
        }
        $d->close();
        $folder->data = $m;
        $folder->status = Folder::STATUS_READY;
        $folder->scaned_at = date('Y-m-d H:i:s');
        $folder->save();
    }

    public function apply($folder)
    {

    }

    /**
     * Get the cache driver for the unique job lock.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function uniqueVia()
    {
        return Cache::driver('redis');
    }
}
