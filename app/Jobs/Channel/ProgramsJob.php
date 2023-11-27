<?php

namespace App\Jobs\Channel;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Program;
use App\Models\Template;
use App\Tools\ChannelGenerator;
use App\Tools\LoggerTrait;

class ProgramsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Channel UUID;
    private $uuid;
    private $group = 'default';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    public function uniqueId()
    {
        return "Channel-".$this->uuid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        $channel = Channel::where('uuid', $this->uuid)->first();

        if(!$channel) {
            $this->error("Channel is null.");
            return 0;
        }

        if(ChannelPrograms::where('channel_id', $channel->id)->exists()) {
            $this->error("Programs exist.");
            return 0;
        }

        $generator = new ChannelGenerator();
        $generator->loadTemplate('default');

        $generator->generate($channel);

        
        $channel->status = Channel::STATUS_READY;
        $channel->save();

        $this->info("Generate programs date: {$channel->air_date} succeed. ");
    }

    private function caculateDuration($str1, $str2)
    {

    }
    
    private function error($msg)
    {
        $msg = date('Y/m/d H:i:s ') . "Channel ".$this->uuid. " error: " . $msg;
        echo $msg.PHP_EOL;
        Log::channel('channel')->error($msg);
    }

    private function info($msg)
    {
        $msg = date('Y/m/d H:i:s ')."Channel ".$this->uuid. " info: " . $msg;
        echo $msg.PHP_EOL;
        Log::channel('channel')->info($msg);
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
