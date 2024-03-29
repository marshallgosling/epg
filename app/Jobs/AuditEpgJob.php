<?php

namespace App\Jobs;

use App\Events\Channel\CalculationEvent;
use App\Models\Audit;
use App\Models\Channel;
use App\Models\Material;
use App\Models\Program;
use App\Models\Record;
use App\Tools\ChannelGenerator;
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
    private $name;
    private $cache;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $name="")
    {
        $this->id = $id;
        $this->name = $name;
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

        if($channel->status != Channel::STATUS_READY) return;
        if($channel->name == 'xkc') $class='\App\Models\Record';
        if($channel->name == 'xki') $class='\App\Models\Record2';
        if($channel->name == 'xkv') $class='\App\Models\Program';
        $this->cache = [];
        $programs = $channel->programs()->get();

        $duration = $this->checkDuration($programs);

        if(!$duration['result']) {
            CalculationEvent::dispatch($channel->id);
            $channel = Channel::find($this->id);
            $programs = $channel->programs()->get();
        }
        $material = $this->checkMaterial($this->cache);
        $total = $this->checkTotal($programs, $channel, $class);

        if(!$total['reason']) {
            $channel = Channel::find($this->id);
            $programs = $channel->programs()->get();
        }
        $check5 = $this->check5seconds($channel, $programs);

        $reason = compact('duration', 'material', 'check5', 'total');
        $comment = '';

        if(!$material['result']) $comment.='存在缺失的物料记录。';
        if(!$duration['result']) $comment.='节目时长已调整，请重新加锁确认。';
        
        $comment .= $total['reason'];
        $comment .= $check5['reason'];

        $audit = new Audit();
        $audit->name = $channel->name;
        $audit->status = $duration['result'] && $material['result'] && $check5['result'] && $total['result'] ? Audit::STATUS_PASS : Audit::STATUS_FAIL;
        $audit->reason = json_encode($reason);
        $audit->admin = $this->name;
        $audit->channel_id = $channel->id;
        $audit->comment = $comment;
        $audit->save();

        $channel->audit_date = now();
        $channel->save();
    }

    private function check5seconds($channel, $programs)
    {
        $start_end = explode(' - ', $channel->start_end);
        $start = strtotime($channel->air_date.' '.$start_end[0]);
        $end = strtotime($channel->air_date.' '.$start_end[1]);
        if($end < $start) return ['result'=>false, 'reason'=>'编单时间不足，请手动添加节目。'];

        //$programs = $channel->programs()->get();
        $program = $programs[count($programs) - 1];
        $data = json_decode($program->data);
        $id = $data->replicate;
        foreach($programs as $pro)
        {
            if($pro->id == $id) {
                $program = $pro;
                $data = json_decode($program->data);
                break;
            }
        }

        $overflow = $end-$start;
        while($overflow>0)
        {
            if(count($data) == 0) break;
            $item = array_pop($data);
            
            $duration = ChannelGenerator::parseDuration($item->duration);
            $overflow -= $duration;
        }

        if($overflow>=-5 && $overflow<0)
        {
            return ['result'=>false, 'reason'=>'异常：编单最后一档节目"'.$item->name.'"播出时间将小于5秒。'];
        }

        if($overflow > 0) {
            return ['result'=>false, 'reason'=>'编单时间异常，系统无法确认播出情况，需手动分析。'];
        }

        return ['result'=>true, 'reason'=>''];
    }

    private function checkMaterial($cache)
    {
        $logs = []; 
        $result = true;
        foreach($cache as $k=>$m)
        {
            if(!$m) {
                $m = new Material();
                $m->unique_no = $k;
                $m->name = '';
                $m->status = 0;
                $m->duration = '';
            }
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
                    $m = Material::where('unique_no', $unique_no)->select(['id','name','unique_no','status','duration'])->first();
                    $this->cache[$unique_no] = $m;
                }
                else {
                    $m = $this->cache[$unique_no];
                }

                if(!$m) {
                    continue;
                }

                if(substr($duration, 0, 8) != substr($m->duration, 0, 8)) {
                    $log = json_decode(json_encode($item), true);
                    $item['duration'] = $m->duration;
                    $log['duration2'] = $m->duration;
                    $log['pro'] = $pro->id;
                    $logs[] = $log;
                    $result = false;
                }
            }

            if($result == false) {
                $pro->data = json_encode($data);
                $pro->save();
            }
            
        }

        return compact('result', 'logs');
    }

    private function checkTotal($programs, $channel, $class=null)
    {
        if(!$class) return ['result'=>true, 'reason'=>''];
        $start_end = explode(' - ', $channel->start_end);
        $start = strtotime($channel->air_date.' '.$start_end[0]);
        $end = strtotime($channel->air_date.' '.$start_end[1]);

        if($start <= $end) return ['result'=>true, 'reason'=>''];
        
        $seconds = $start - $end;

        if($seconds > 1800)
        {
            return ['result'=>false, 'reason'=>'编单时间异常，系统无法确认播出情况，需手动分析。'];
        }

        $propose = floor($seconds / 3);

        if($channel->name == 'xkv')
        {
            return ['result'=>false, 'reason'=>'编单时间异常，系统无法确认播出情况，需手动分析。'];
        }

        $program = $programs[count($programs) - 1];

        $class::loadBumpers();

        $break_level = 2;

        $air = strtotime($program->end_at);
        $data = json_decode($program->data, true);
                
        //$schedule_end = strtotime($channel->air_date.' '.$program->schedule_start_at) + $scheduledDuration;
        while($propose > 0)
        {
            //if($duration > $scheduledDuration) break;
            // 如果当前累加的播出时间和计划播出时间差距大于5分钟，
            // 凑时间，凑节目数
            $res = $this->addBumperItem($break_level, $propose, $air, $class);
            if(is_array($res)) {
                $data[] = $res['line'];
                $propose -= $res['seconds'];
                $air += $res['seconds'];
                        //$this->info("add Bumper: ".json_encode($res, JSON_UNESCAPED_UNICODE));
            }
            else {
                // 4次循环后，还是没有找到匹配的节目，则跳出循环
                $break_level --;
            }

            if($break_level < 0) {
                break;
            }
        }
        
        $program->data = json_encode($data);
        $program->save();

        CalculationEvent::dispatch($channel->id);

        return ['result'=>true, 'reason'=>'已自动调整节目编单时长。'];
        
    }

    public function addBumperItem($break_level, $propose, $air, $class)
    {
        $item = $class::findBumper($break_level);

        if(!$item) return false;
        //$this->info("find bumper: {$item->name} {$item->duration}");
        $seconds = ChannelGenerator::parseDuration($item->duration);
        if($seconds > (2*$propose)) return false;
        
        $category = $item->category;
        if(is_array($category)) $category = array_pop($category);
        //$this->info("air time: ".date('Y/m/d H:i:s', $air). " {$air}, schedule: ".date('Y/m/d H:i:s', $schedule_end));
                   
        $line = ChannelGenerator::createItem($item, $category, date('H:i:s', $air));
        $air += $seconds;
        $line['end_at'] = date('H:i:s', $air);

        return compact('line', 'seconds');
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
