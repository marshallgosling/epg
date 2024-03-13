<?php

namespace App\Jobs;

use App\Events\Channel\CalculationEvent;
use App\Models\Audit;
use App\Models\Channel;
use App\Models\Material;
use Encore\Admin\Facades\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class AuditEpgJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;
    private $cache;

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

        if($channel->status != Channel::STATUS_READY)
        {
            return;
        }

        $this->cache = [];
        $programs = $channel->programs()->get();

        $duration = $this->checkDuration($programs);

        if(!$duration['result']) CalculationEvent::dispatch($channel->id);

        $material = $this->checkMaterial($this->cache);

        $reason = compact('duration', 'material');

        $audit = new Audit();
        $audit->name = $channel->name;
        $audit->status = $duration['result'] && $material['result'] ? Audit::STATUS_PASS : Audit::STATUS_FAIL;
        $audit->reason = json_encode($reason);
        $audit->admin = Admin::user()->name;
        $audit->channel_id = $channel->id;
        $audit->save();

        $channel->audit_date = now();
        $channel->save();
    }

    private function checkMaterial($cache)
    {
        $logs = []; 
        $result = true;
        foreach($cache as $m)
        {
            if($m->status != Material::STATUS_READY)
            {
                $logs[] = $m;
                $result = false;
            }
        }
        return compact('result', 'logs');
    }

    private function checkDuration($programs)
    {
        $logs = [];
        $result = true;

        foreach($programs as $pro)
        {
            $data = json_decode($pro->data, true);

            if(array_key_exists('replicate', $data)) continue;
            foreach($data as &$item)
            {
                $duration = $item['duration'];
                $unique_no = $item['unique_no'];

                if(!array_key_exists($unique_no, $this->cache))
                {
                    $m = Material::where('unique_no', $unique_no)->first();
                    $cache[$unique_no] = $m;
                }
                else {
                    $m = $this->cache[$unique_no];
                }

                if(substr($duration, 0, 8) != substr($m->duration, 0, 8)) {
                    $log = json_decode(json_encode($item), true);
                    $item['duration'] = $m->duration;
                    $log['duration2'] = $m->duration;
                    $log['pro'] = $pro->id;
                    $logs[] = $log;
                }
            }

            $pro->data = json_encode($data);
            if($pro->isDirty()) {
                $pro->save();
                $result = false;
            }
        }

        return compact('result', 'logs');
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
