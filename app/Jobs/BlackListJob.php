<?php

namespace App\Jobs;

use App\Events\Channel\CalculationEvent;
use App\Models\BlackList;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Notification;
use App\Models\Program;
use App\Tools\ChannelGenerator;
use App\Tools\LoggerTrait;
use App\Tools\Notify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BlackListJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggerTrait;

    // Job ID;
    private $id;

    // Action
    private $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $action)
    {
        $this->id = $id;
        $this->action = $action;
        $this->log_channel = 'black';
        $this->log_print = false;
    }

    public function uniqueId()
    {
        return $this->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $channels = DB::table('channel')->whereBetween('air_date', [$start_at, $end_at])
        //                                 ->select('id','air_date','uuid')
        //                                 ->orderBy('air_date')->get();
        //$blacklist = BlackList::get()->pluck('keyword');
        $model = BlackList::find($this->id);
        if(!$model) return;
        
        $action = $this->action;

        if(in_array($action, ['scan', 'apply']))
        {
            $this->$action($model);
        }   
        else
        {
            $model->status = BlackList::STATUS_READY;
            $model->save();
        }
        
    }

    private function apply($model)
    {
        $hasdata = false;
        if($model->data != null) $hasdata = true;

        $data = json_decode($model->data);

        if(!$data) $hasdata = false;
        else $hasdata = array_key_exists('xkv', $data) && array_key_exists('program', $data);
       
        if(!$hasdata) {
            $model->status = BlackList::STATUS_READY;
            $model->save();

            Notify::fireNotify(
                'xkv',
                Notification::TYPE_BLACKLIST, 
                "应用 {$model->keyword} 完成. ", 
                "数据为空，直接退出。",
                Notification::LEVEL_WARN
            );
            return;
        }

        foreach($data->xkv as $item)
        {
            foreach($item->programs as $pro)
            {
                $program = ChannelPrograms::find($pro->id);

                $json = json_decode($program->data, true);

                foreach($pro->items as $line)
                {
                    $old = $json[$line['offset']];

                    $new = Program::findRandom($old['category'], 3600);
                    $l = ChannelGenerator::createItem($new, $old['category'], $old['start_at']);
                    $json[$line['offset']] = $l;
                }

                $program->data = json_encode($json);
                $program->save();

            }

            CalculationEvent::dispatch($item->id, 0);
        }

        $ids = [];
        foreach($data->program as $program)
        {
            $ids[] = $program->id;
        }

        if(count($ids)) {
            Program::whereIn('id', $ids)->update(['black' => $model->id]);
        }

        $data->applied = date('Y/m/d H:i:s');
        $model->status = BlackList::STATUS_READY;
        $model->data = json_encode($data);
        $model->save();

        Notify::fireNotify(
            'xkv',
            Notification::TYPE_BLACKLIST, 
            "应用黑名单 {$model->keyword} 完成. "
        );
        
    }

    private function scan($model)
    {
        $xkvs = Channel::where('air_date', '>', date('Y/m/d'))->with('programs')->select('id','air_date','name')->get();
        $data = ['xkv'=>[],'program'=>[],'applied'=>''];
        $property = $model->group;

        if($xkvs)foreach($xkvs as $xkv)
        {
            $programs = $xkv->programs()->get();

            $_channel = ["id"=>$xkv->id, "date"=>$xkv->air_date, "group"=>$xkv->name, 'programs'=>[]];

            foreach($programs as $pro)
            {
                $items = json_decode($pro->data, true);
                if(array_key_exists('replicate', $items)) continue;

                $_program = ["id"=>$pro->id,"name"=>$pro->name,"start_at"=>$pro->start_at, 'items'=>[]];
                
                foreach($items as $idx=>$item) {
               
                    if(!array_key_exists($property, $item)) continue;

                        if(Str::contains($item[$property], $model->keyword))
                        {
                            $item['offset'] = $idx;
                            $_program['items'][] = $item;
                        }
                    
                }

                $_channel['programs'] = $_program;
            }

            $data[$xkv->name][] = $_channel;
        }

 
        $programs = Program::where($property, 'like', '%'.$model->keyword.'%')
            ->where('black', 0)->select('id', $property)->get();
        
        if($programs)foreach($programs as $pro)
        {
            // if($property == 'artist') {
            //     $artists = explode(' ', str_replace('/', ' ', $pro->artist));
            //     if(in_array($model->keyword, $artists)) {
            //         $data['program'][] = $pro->toArray();
            //         break;
            //     }
    
    
            //     $artists = explode(' ', str_replace('/', ' ', $pro->co_artist));
            //     if(in_array($model->keyword, $artists)) {
            //         $data['program'][] = $pro;
            //         break;
            //     }
            // }
            // else{
            $items = explode(' ', str_replace('/', ' ', $pro->$property));
            foreach($items as $item)
                if(Str::contains($item, $model->keyword))
                {
                    $data['program'][] = $pro->toArray();
                    break;
                }
            //}
            
        }

        $model->status = BlackList::STATUS_READY;
        $model->data = json_encode($data);
        $model->scaned_at = date('Y-m-d H:i:s');
        $model->save();

        Notify::fireNotify(
            'xkv',
            Notification::TYPE_BLACKLIST, 
            "扫描黑名单 {$model->keyword} 完成. "
        );
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
