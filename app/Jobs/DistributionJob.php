<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Material;
use App\Models\Notification;
use App\Tools\Exporter\BvtExporter;
use App\Tools\Exporter\XmlReader;
use App\Tools\Notify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DistributionJob implements ShouldQueue, ShouldBeUnique
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
        $channel = Channel::findOrFail($this->id);

        $is_today = $channel->air_dat == date('Y-m-d');

        if($channel->status == Channel::STATUS_READY && $channel->audit_status == Channel::AUDIT_PASS)
        {
            $air = date('Y-m-d', strtotime($channel->air_date));
            if(!Storage::disk('xml')->exists($channel->name.'_'.$air.'.xml'))
                return;
            $file = Storage::disk('xml')->get($channel->name.'_'.$air.'.xml');
            $items = XmlReader::parseXml($file);
            
            $fail = config('IGNORE_MATERIAL_CHECK', 'false') == 'true' ? false : DB::table('material')->whereIn('unique_no', array_unique($items))
                        ->where('status', '<>', Material::STATUS_READY)->select(['name','unique_no'])
                        ->pluck('name', 'unique_no')->toArray();
                if($fail)
                {
                    Notify::fireNotify($channel->name, Notification::TYPE_XML, '分发格非串联单错误', 
                        '串联单'.$channel->air_date.'存在物料状态不可用的节目内容，'.implode(',', array_values($fail)),
                        Notification::LEVEL_ERROR);
                    //$this->warn("error {$ch->name} {$air}");
                }
                else
                {
                    $channel->distribution_date = date('Y-m-d H:i:s');
                    $channel->save();
                    //$this->info("save distribution date {$ch->name} {$air}");

                    if($is_today) $path = config('BVT_LIVE_PATH', false) ? config('BVT_LIVE_PATH').'\\'.BvtExporter::NAMES[$channel->name].'\\'.BvtExporter::NAMES[$channel->name].'.xml' : false;
                    else $path = config('BVT_XML_PATH', false) ? config('BVT_XML_PATH').'\\'.BvtExporter::NAMES[$channel->name].'_'.$air.'.xml': false; 
                    
                    if($path)
                        file_put_contents($path, $file);
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
