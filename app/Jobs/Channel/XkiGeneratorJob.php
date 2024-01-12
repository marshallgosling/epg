<?php

namespace App\Jobs\Channel;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Notification;
use App\Tools\ChannelGenerator;
use App\Tools\Generator\XkiGenerator;
use App\Tools\LoggerTrait;
use App\Tools\Notify;
use Illuminate\Support\Facades\Storage;

class XkiGeneratorJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggerTrait;

    /**
     * Channel Group
     *  
     */ 
    private $group = 'xki';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->log_channel = 'channel';
        $this->log_print = false;
    }

    public function uniqueId()
    {
        return "BatchChannel-".$this->group;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {  
        $generator = ChannelGenerator::getGenerator('xki');

        if(Storage::disk('data')->exists(XkiGenerator::STALL_FILE))
        {
            Notify::fireNotify(
                $this->group,
                Notification::TYPE_GENERATE, 
                "节目单自动生成工具遇到错误，需要人工干预", 
                "您有未处理的节目单模版数据错误，请先进入临时模版页面，解决模版问题，然后点击解决问题。",
                Notification::LEVEL_WARN
            );
            $generator->reset();
            return 0;
        }

        $test = $generator->test();
        if($test) {
            Notify::fireNotify(
                $this->group,
                Notification::TYPE_GENERATE, 
                "节目单自动生成工具遇到错误，需要人工干预", 
                "您有未处理的节目单模版数据错误，请先进入临时模版页面，解决模版问题，然后点击解决问题。",
                Notification::LEVEL_WARN
            );

            $generator->reset();
            return 0;
        }
        
        $generator->generate();

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
