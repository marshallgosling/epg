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

    private $group;
    private $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($group, $action='none')
    {
        $this->group = $group;
        $this->action = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $jobs = EpgJob::where('group_id', $this->group)->orderBy('id', 'desc')->get()->toArray();

        // $templates = Template::with('records')->where('group_id', $this->group)->orderBy('sort', 'asc')->get();
        // $data = json_encode($templates->toArray());
        // Storage::disk('data')->put($this->group.'_reset_template_'.date('YmdHis').'.json', $data);
        $json = $this->group."_saved_template.json";
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
            if($this->action == 'clear') $this->clearChannel($data['channels']);
        }
        
        return 0;

        
    }

    private function reverseTemplate($json)
    {
        $job = json_decode($json, true);
        
        if(!key_exists('template', $job)) return false;
        
        $template = $job['template'];
        
        foreach($template['records'] as $record)
        {
            $item = TemplateRecords::find($record['id']);

            if($item) 
            {
                $item->data['unique_no'] = $record['data']['unique_no'];
                $item->data['name'] = $record['data']['name'];
                $item->data['result'] = $record['data']['result'];
                $item->save();
            }

        }

        return true;
    }

    private function reverseTemplate2($json)
    {
        $job = json_decode($json, true);
        $data = $job[count($job)-1];
        foreach($data['data'] as $template)
        {
            foreach($template['records'] as $record)
            {
                $item = ChannelPrograms::find($record['id']);
                if($item)
                {
                    $item->data['unique_no'] = $record['data']['unique_no'];
                    $item->data['name'] = $record['data']['name'];
                    $item->data['result'] = $record['data']['result'];
                    $item->save();
                }
            }
        }
    }

    private function clearChannel($channels)
    {
        
        foreach($channels as $day)
        {
            $c = Channel::where('id', $day['id'])->delete();
            ChannelPrograms::where('channel_id', $c->id)->delete();
        }
    }

    public function uniqueId()
    {
        return $this->group;
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
