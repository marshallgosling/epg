<?php

namespace App\Jobs\Material;

use App\Models\Channel;
use App\Tools\Exporter\BvtExporter;
use App\Tools\Exporter\XmlReader;
use App\Models\LargeFile;
use App\Models\Material;
use App\Models\Notification;
use App\Tools\ChannelGenerator;
use App\Tools\LoggerTrait;
use App\Tools\Material\MediaInfo;
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

class MediaInfoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggerTrait;

    // Job ID;
    private $id;

    private $action = '';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $action='')
    {
        $this->id = $id;
        $this->action = $action;
        $this->log_channel = 'mediainfo';
        $this->log_print = false;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $action = $this->action;
        if(in_array($action, ['sync', 'view', 'process', 'distribute']))
        {
            $this->$action();
        }
        
    }

    /**
     * Distribute EPG xml files
     */
    private function distribute()
    {
        $channel = Channel::find($this->id);

        if(!$channel) return;

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

    private function view()
    {
        $material = Material::find($this->id);
        if(!$material) return;
        $unique_no = $material->unique_no;

        if(file_exists($material->filepath)) {
            try{
                $info = MediaInfo::getRawInfo($material);
                Cache::set('mediainfo_'.$unique_no, $info, 3600);
            }catch(\Exception $e)
            {
                $info = false;
            }

        }
    }

    private function process()
    {
        $largefile = LargeFile::find($this->id);
        if(!$largefile) return;
        $folders = explode(PHP_EOL, config('MEDIA_SOURCE_FOLDER', ''));
        $filepath = Storage::path(config("aetherupload.root_dir") .'\\'. str_replace('_', '\\', $largefile->path));
        $targetpath = $folders[$largefile->target_path].$largefile->name;

        if(file_exists($filepath))
        {
            @copy($filepath, $targetpath);

            if(!file_exists($targetpath))
            {
                $largefile->status = LargeFile::STATUS_ERROR;
                $largefile->save();
                return;
            }

            $largefile->status = LargeFile::STATUS_READY;
            $largefile->save();
            @unlink($filepath);

            $names = explode('.', $largefile->name);

                if(count($names) != 3) {
                    
                    return;
                }
                
                $unique_no = $names[1];

                $material = Material::where('unique_no', $unique_no)->first();

                if(!$material) {
                    $material = new Material();
                    $material->unique_no = $unique_no;
                    $material->name = $names[0];
                    $material->filepath = $targetpath;
                    $material->status = Material::STATUS_EMPTY;
                    $group = preg_replace('/(\d+)$/', "", $names[0]);
                    $material->group = trim(trim($group), '_-');
                    $material->channel = 'xkc';
                    $material->save();
                }

                MediaInfoJob::dispatch($material->id, 'sync')->onQueue('media');
                
        }
    }

    
    private function sync()
    {
        $material = Material::find($this->id);
        if(!$material) return;
        $unique_no = $material->unique_no;
        $filepath = $material->filepath;
        if(empty($filepath))
        {
            $filepath = MediaInfo::scanPath($material->name.'.'.$unique_no.'.mxf');
        }

        if($filepath) {
            $material->filepath = $filepath;
        }

        if(file_exists($material->filepath)) {

            try{
                $info = MediaInfo::getInfo($material);
            }catch(\Exception $e)
            {
                $info = false;
            }
            
            if($info) {
                $status = Material::STATUS_READY;
                $material->frames = $info['frames'];
                $material->size = $info['size'];
                $material->duration = ChannelGenerator::parseFrames((int)$info['frames']);
            }
            else {
                $status = Material::STATUS_ERROR;
            }
            
            $material->status = $status;
            if($material->isDirty()) $material->save();

            if($info) {
                $duration = $material->duration;
                $seconds = ChannelGenerator::parseDuration($duration);
    
                $data = compact('status', 'duration', 'seconds');
    
                foreach(['records', 'record2', 'program'] as $table)
                    DB::table($table)->where('unique_no', $unique_no)->update($data);

                Notify::fireNotify($material->channel, Notification::TYPE_MATERIAL, "同步素材信息成功", "播出号:{$material->unique_no}，数据 Frames: {$info['frames']}。");
            }
            else {
                foreach(['records', 'record2', 'program'] as $table)
                    DB::table($table)->where('unique_no', $unique_no)->update(['status'=>$status]);

                Notify::fireNotify($material->channel, Notification::TYPE_MATERIAL, "同步素材信息失败", "播出号:{$material->unique_no}，媒体文件: {$material->filepath} 不可读。");
            
            }
            
        }
        else {
            foreach(['records', 'record2', 'program','material'] as $table)
                DB::table($table)->where('unique_no', $unique_no)->update(['status'=>Material::STATUS_EMPTY]);
            Notify::fireNotify($material->channel, Notification::TYPE_MATERIAL, "同步素材信息失败", "播出号:{$material->unique_no}，媒体文件不存在。");
            
        }
        
    }

    public function uniqueId()
    {
        return $this->action.'-'.$this->id;
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
