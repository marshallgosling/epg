<?php

namespace App\Jobs\Material;

use App\Models\Material;
use App\Models\Notification;
use App\Tools\ChannelGenerator;
use App\Tools\LoggerTrait;
use App\Tools\Material\MediaInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MediaInfoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggerTrait;

    // Job ID;
    private $id;

    private $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $action='sync')
    {
        $this->id = $id;
        $this->action = $action;
        $this->log_channel = 'mediainfo';
        $this->log_print = false;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $action = $this->action;
        if(in_array($action, ['sync', 'view']))
        {
            $this->$action();
        }
    }

    private function view()
    {
        $material = Material::findOrFail($this->id);
        $unique_no = $material->unique_no;

        if(file_exists($material->filepath)) {
            try{
                $info = MediaInfo::getRawInfo($material);

                Cache::set('mediainfo_'.$unique_no, $info, 300);
            }catch(\Exception $e)
            {
                $info = false;
            }

        }
    }

    
    private function sync()
    {
        $material = Material::findOrFail($this->id);
        $unique_no = $material->unique_no;

        if(file_exists($material->filepath)) {
            try{
                $info = MediaInfo::getInfo($material);
            }catch(\Exception $e)
            {
                $info = false;
            }
            
            if($info) {
                $status = Material::STATUS_READY;
                $material->frames = $info['frames'];
                $material->size = $info['size'];
                $material->duration = ChannelGenerator::parseFrames((int)$info['frames']);
            }
            else {
                $status = Material::STATUS_ERROR;
            }
            
            $material->status = $status;
            if($material->isDirty()) $material->save();

            if($info) {
                $duration = $material->duration;
                $seconds = ChannelGenerator::parseDuration($duration);
    
                $data = compact('status', 'duration', 'seconds');
    
                foreach(['records', 'record2', 'program'] as $table)
                    DB::table($table)->where('unique_no', $unique_no)->update($data);
            }
            else {
                foreach(['records', 'record2', 'program'] as $table)
                    DB::table($table)->where('unique_no', $unique_no)->update(['status'=>$status]);
            }
            
        }
        else {
            foreach(['records', 'record2', 'program','material'] as $table)
                DB::table($table)->where('unique_no', $unique_no)->update(['status'=>Material::STATUS_EMPTY]);
        }
        
    }

    public function uniqueId()
    {
        return $this->action.'-'.$this->id;
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
