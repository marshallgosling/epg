<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Notification;
use App\Tools\LoggerTrait;
use App\Tools\Notify;
use App\Tools\Statistic\StatisticProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class StatisticJob implements ShouldQueue, ShouldBeUnique
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
        $this->log_channel = 'statistic';
        $this->log_print = false;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $statistic = new StatisticProgram();

        $channel = Channel::find($this->id);
        if(!$channel) return;

        if($channel->lock_status != Channel::LOCK_ENABLE) {
            $this->info("频道 {$channel->name} 日期 {$channel->air_date} 还没有锁定");
            return;
        }

        if(! in_array($channel->status, [Channel::STATUS_READY, Channel::STATUS_DISTRIBUTE]) ) {
            $this->info("频道 {$channel->name} 日期 {$channel->air_date} 节目单状态不为“正常”");
            return;
        }

        $this->info("载入频道 {$channel->name} 日期 {$channel->air_date} 数据。");
        $statistic->load($channel);
        $results = $statistic->scan();

        if($results['result']){
            $this->info("统计数据成功，保存数据库并将替换已有数据（如存在）。");
            $statistic->store();

            Notify::fireNotify(
                $channel->name,
                Notification::TYPE_STATISTIC, 
                "{$channel->name} {$channel->air_date} 统计数据成功", "频道 {$channel->name} 日期 {$channel->air_date} 成功，保存数据库并将替换已有数据（如存在）。"
            );
        }
        else {
            $this->error($results['msg']);
            Notify::fireNotify(
                $channel->name,
                Notification::TYPE_STATISTIC, 
                "{$channel->name} {$channel->air_date} 统计数据失败", $results['msg'], Notification::LEVEL_ERROR
            );
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
