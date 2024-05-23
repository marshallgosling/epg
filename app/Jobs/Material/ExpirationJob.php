<?php

namespace App\Jobs\Material;

use App\Models\Agreement;
use App\Models\Expiration;
use App\Models\Record;
use App\Tools\LoggerTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ExpirationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggerTrait;

    private $id;
    private $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $action='update')
    {
        $this->id = $id;
        $this->action = $action;
        $this->log_channel = 'daily';
        $this->log_print = false;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $exp = Expiration::find($this->id);
        //$exp = Expiration::where('name', $this->id)->first();
        if(!$exp) return;

        $agreement = Agreement::find($exp->agreement_id);
        if(!$agreement) return;

        if(empty($exp->name)) return;

        if($this->action == 'update')
        {
            Record::where('episodes', $exp->name)->update(['air_date'=>$agreement->start_at, 'expired_date'=>$agreement->end_at]);
            $this->info("update records expiration date : {$exp->name} {$agreement->name} {$agreement->start_at} - {$agreement->end_at}");
        }

        if($this->action == 'daily')
        {
            $now = strtotime(date('Y-m-d'));
            $ex = strtotime($agreement->end_at);
            if($ex < $now)
            {
                Record::where('episodes', $exp->name)->update(['status'=>Record::STATUS_EMPTY]);
                $this->info("update records expiration status : {$exp->name} {$agreement->name} {$agreement->start_at} - {$agreement->end_at}");
            }
            //Record::where('episode', $exp->name)->update(['air_date'=>$agreement->start_at, 'expired_date'=>$agreement->end_at]);
        }

        return 0;

        
    }


    public function uniqueId()
    {
        return $this->id.'-'.$this->action;
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
