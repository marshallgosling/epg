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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
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
        $material = Material::findOrFail($this->id);

        if(file_exists($material->filepath)) {
            $info = MediaInfo::getInfo($material);

            $material->frames = $info['frames'];
            $material->size = $info['size'];
            $material->duration = ChannelGenerator::parseFrames((int)$info['frames']);
            $material->save();

            $status = Material::STATUS_READY;
            $unique_no = $material->unique_no;
            $duration = $material->duration;
            $seconds = ChannelGenerator::parseDuration($duration);

            $data = compact('status', 'duration', 'seconds');
            foreach(['records', 'record2', 'program'] as $table)
                DB::table($table)->where('unique_no', $unique_no)->update($data);
        }
        
    }

    public function uniqueId()
    {
        return $this->id;
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
