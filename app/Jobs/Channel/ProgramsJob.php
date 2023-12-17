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
use App\Tools\Notify;
use App\Models\Notification;

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
            Notify::fireNotify(
                $channel->name,
                Notification::TYPE_GENERATE, 
                "生成节目编单 {$channel->name}_{$channel->air_date} 失败. ", 
                "频道 {$this->uuid} 节目编单已存在，退出自动生成，请先清空该编单数据。",
                Notification::LEVEL_WARN
            );
            return 0;
        }

        $channel->status = Channel::STATUS_RUNNING;
        $channel->save();

        $generator = new ChannelGenerator();
        $generator->loadTemplate($channel->name);

        if($channel->name == 'xkc')
            $start_end = $generator->generateXkc($channel);
        else
            $start_end = $generator->generate($channel);
        
        $channel->status = Channel::STATUS_READY;
        $channel->start_end = $start_end;
        $channel->save();

        Notify::fireNotify(
            $channel->name,
            Notification::TYPE_GENERATE, 
            "生成节目编单 {$channel->name}_{$channel->air_date} 数据成功. ", 
            "频道节目时间 $start_end"
        );

        $this->info("生成节目编单 {$channel->air_date} 数据成功. ");

        ChannelGenerator::writeTextMark($channel->name, $channel->air_date);
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
