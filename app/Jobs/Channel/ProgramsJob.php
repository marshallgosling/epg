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
use App\Tools\ChannelGenerator;
use App\Tools\LoggerTrait;

class ProgramsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggerTrait;

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
        $this->log_channel = 'channel';
        $this->log_print = false;
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
            $this->error("频道 {$this->uuid} 不存在");
            return 0;
        }

        if(ChannelPrograms::where('channel_id', $channel->id)->exists()) {
            $this->error("频道 {$this->uuid} 节目编单已存在，退出自动生成，请先清空该编单数据。");
            return 0;
        }

        $generator = new ChannelGenerator();
        $generator->loadTemplate($channel, $channel->name);

        if($channel->name == 'xkc')
            $generator->generateXkc($channel);
        else
            $generator->generate($channel);
        
        $channel->status = Channel::STATUS_READY;
        $channel->save();

        $this->info("生成节目编单 {$channel->air_date} 数据成功. ");
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
