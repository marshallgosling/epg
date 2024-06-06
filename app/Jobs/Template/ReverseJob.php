<?php

namespace App\Jobs\Template;

use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\EpgJob;
use App\Models\Template;
use App\Models\TemplatePrograms;
use App\Models\TemplateRecords;
use App\Tools\ChannelDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ReverseJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;
    private $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $action='none')
    {
        $this->id = $id;
        $this->action = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $job = EpgJob::find($this->id);
        if(!$job) return 0;

        $json = $job->file;
        if(Storage::exists($json))
        {
            $data = json_decode(Storage::get($json), true);
            foreach($data['templates'] as $template)
            {
                foreach($template['records'] as $record)
                {
                    $record['data'] = json_encode($record['data']);
                    TemplateRecords::where('id', $record['id'])->update($record);
                }
            }
            Storage::delete($json);
            $job->delete();
            if($this->action == 'clear') $this->clearChannel($data['channels']);
        }
        
        return 0;

        
    }

    private function clearChannel($channels)
    {
        
        foreach($channels as $day)
        {
            $c = Channel::find($day['id']);
            if($c) {
                ChannelPrograms::where('channel_id', $c->id)->delete();
                $c->delete();
            }
            
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
