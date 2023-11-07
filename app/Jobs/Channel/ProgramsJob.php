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

class ProgramsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Channel UUID;
    private $uuid;

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
        $templates = Template::where('group_id', 'default')->lazy();

        $channel = Channel::where('uuid', $this->uuid)->first();

        if(!$channel) {
            $this->error("Channel {$this->uuid} is null.");
            return 0;
        }

        if(ChannelPrograms::where('channel_id', $channel->id)->exists()) {
            $this->error("Channel {$this->uuid} 's programs exist.");
            return 0;
        }

        foreach($templates as $t) {
            $c = new ChannelPrograms();
            $c->name = $t->name;
            $c->schedule_start_at = $t->start_at;
            $c->schedule_end_at = $t->end_at;
            $c->channel_id = $channel->id;
            $c->start_at = $channel->air_date.' '.$t->start_at;
            $c->duration = 0;
            $c->version = '1';
            
            $data = [];
            $programs = $t->programs();
            foreach($programs as $p) {
                $c = $p->category;
                $item = Program::findOneOrderByRandom($c[0]);

                if($item) {
                    $data[] = $item->toArray();
                    $c->duration += (int)$item->duration;
                }
            }
            $c->data = $data;

            $c->save();
        }

        $channel->status = Channel::STATUS_NORMAL;
        $channel->save();

        $this->info("Generate channel {$this->uuid} programs succeed.");
    }

    private function error($msg, $enterspace="\n")
    {
        $msg = date('Y/m/d H:i:s ') . "Channel ".$this->uuid. " error: " . $msg;
        echo $msg.$enterspace;
        Log::channel('channel')->error($msg);
    }

    private function info($msg, $enterspace="\n")
    {
        $msg = date('Y/m/d H:i:s ')."Channel ".$this->uuid. " info: " . $msg;
        echo $msg.$enterspace;
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
