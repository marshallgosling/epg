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
        $jobs = EpgJob::where('group_id', $this->group)->orderBy('id', 'desc')->get()->toArray();

        $templates = Template::with('records')->where('group_id', $this->group)->orderBy('sort', 'asc')->get();
        $data = json_encode($templates->toArray());
        Storage::disk('data')->put($this->group.'_reset_template_'.date('YmdHis').'.json', $data);

        $job = $jobs[0];
        if(count($jobs) == 1) {

            foreach($templates as $t)
            {
                $records = $t->records;

                foreach($records as $model)
                {
                    $data = $model->data;
                    if(key_exists('unique_no', $data)) $data['unique_no'] = '';
                    if(key_exists('result', $data)) $data['result'] = '';
                    if(key_exists('name', $data)) $data['name'] = '';

                    if($model->type == TemplateRecords::TYPE_RANDOM) $data['episodes'] = '';

                    $model->data = $data;
                    if($model->isDirty()) $model->save();
                }
            }
        }
        else {
            // $json = Storage::get($job->file);
            // $ret = $this->reverseTemplate($json);
            // if(!$ret) {
                    
                $reverse = $job[1];
                $json = Storage::get($reverse->file);
                $this->reverseTemplate2($json);
            // }
        }

        if($this->action == 'clear') $this->clearChannel($json);

        
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

    private function clearChannel($json)
    {
        $job = json_decode($json, true);
        if(key_exists('data', $job)) $data = $job['data'];
        else $data = $job;
        foreach($data as $day)
        {
            $c = Channel::find($day['id']);
            if(!$c) continue;
            ChannelPrograms::where('channel_id', $c->id)->delete();
            $c->delete();

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
