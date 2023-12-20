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
use App\Tools\Generator\GenerationException;
use App\Tools\Generator\XkcGenerator;
use App\Tools\LoggerTrait;
use App\Tools\Notify;
use Illuminate\Support\Facades\Storage;

class RecordJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggerTrait;

    // Channel UUID;
    //private $uuid;
    private $group = 'default';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($group)
    {
        $this->group = $group;
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
        if(Storage::disk('data')->exists("generate_stall"))
        {
            Notify::fireNotify(
                $this->group,
                Notification::TYPE_GENERATE, 
                "节目单自动生成工具遇到错误，需要人工干预", 
                "您有未处理的节目单模版数据错误，请先进入临时模版页面，解决模版问题，然后点击解决问题。",
                Notification::LEVEL_WARN
            );
            return 0;
        }

        $error = false;

        $channels = Channel::where('name', $this->group)->where('status', Channel::STATUS_WAITING)->orderBy('air_date')->get();

        if(!$channels) {
            $this->error("频道 {$this->group} 不存在");
            return 0;
        }

        $generator = new XkcGenerator($this->group);
        $generator->makeCopyTemplate();
        $generator->loadTemplate();

        foreach($channels as $channel) {

            if(ChannelPrograms::where('channel_id', $channel->id)->exists()) {
                $this->error("频道 {$channel->uuid} 节目编单已存在，退出自动生成，请先清空该编单数据。");
                Notify::fireNotify(
                    $channel->name,
                    Notification::TYPE_GENERATE, 
                    "生成节目编单 {$channel->name}_{$channel->air_date} 失败. ", 
                    "频道 {$channel->uuid} 节目编单已存在，退出自动生成，请先清空该编单数据。",
                    Notification::LEVEL_WARN
                );
                $channel->status = Channel::STATUS_ERROR;
                $channel->save();
                continue;
            }

            if($error) {
                $channel->status = Channel::STATUS_EMPTY;
                $channel->save();
                continue;
            }

            $channel->status = Channel::STATUS_RUNNING;
            $channel->save();

            try {
                $start_end = $generator->generate($channel);
            }catch(GenerationException $e)
            {
                Notify::fireNotify(
                    $channel->name,
                    Notification::TYPE_GENERATE, 
                    "生成节目编单 {$channel->name}_{$channel->air_date} 数据失败. ", 
                    "详细错误:".$e->getMessage()."\n".$e->desc, 'error'
                );
                $channel->start_end = '';
                $channel->status = Channel::STATUS_EMPTY;
                $channel->save();
                Storage::disk('data')->put("generate_stall", $e->desc);
                $error = true;
                continue;
            }

            $generator->saveTemplateState();

            $channel->status = Channel::STATUS_READY;
            $channel->start_end = $start_end;
            $channel->save();

            Notify::fireNotify(
                $channel->name,
                Notification::TYPE_GENERATE, 
                "生成节目编单 {$channel->name}_{$channel->air_date} 数据成功. ", 
                "频道节目时间 $start_end"
            );

            $this->info("生成节目编单 {$channel->name}_{$channel->air_date} 数据成功. ");

            ChannelGenerator::writeTextMark($channel->name, $channel->air_date);
                  
        }

        if(!$error) {
            $generator->cleanTempData();
        }
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
