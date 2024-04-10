<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Tools\ChannelDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class EpgJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $channel = Channel::find($this->id);
        if(!$channel) return;

        if(in_array($channel->status, [Channel::STATUS_READY, Channel::STATUS_DISTRIBUTE])
             && $channel->lock_status == Channel::LOCK_ENABLE)
        {
            ChannelDatabase::removeEpg($channel);
            ChannelDatabase::saveEpgToDatabase($channel);

            ExportJob::dispatch($this->id);
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
