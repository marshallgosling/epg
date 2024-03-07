<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Material;
use App\Models\Notification;
use App\Tools\Exporter\BvtExporter;
use App\Tools\Exporter\XmlReader;
use App\Tools\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class dailyJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:job {action?} {args?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily jobs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action') ?? "xml";
        $args = $this->argument('args') ?? "";

        if(in_array($action, ['xml', 'check', 'scan']))
            $this->$action($args);
        
        return 0;
    }

    private function xml($args)
    {
        $now = $args ? strtotime($args) : (time() + 7 * 86400);

        $is_today = $args == date('Y-m-d');

        $list = Channel::where('status', Channel::STATUS_READY)
                ->where('audit_status', Channel::AUDIT_PASS)
                ->where('distribution_date', null)
                ->where('air_date', date('Y-m-d', $now))
                //->orderBy('air_date')
                ->get();

        if(!$list) return;
        foreach($list as $ch)
        {
            $air = date('Y-m-d', strtotime($ch->air_date));
            if(!Storage::disk('xml')->exists($ch->name.'_'.$air.'.xml'))
                continue;
            $file = Storage::disk('xml')->get($ch->name.'_'.$air.'.xml');
            
            $items = XmlReader::parseXml($file);

            if($items)
            {
                
                $fail = config('IGNORE_MATERIAL_CHECK', 'false') == 'true' ? false : DB::table('material')->whereIn('unique_no', array_unique($items))
                        ->where('status', '<>', Material::STATUS_READY)->select(['name','unique_no'])
                        ->pluck('name', 'unique_no')->toArray();
                if($fail)
                {
                    Notify::fireNotify($ch->name, Notification::TYPE_XML, '分发格非串联单错误', 
                        '串联单'.$ch->air_date.'存在物料状态不可用的节目内容，'.implode(',', array_values($fail)),
                        Notification::LEVEL_ERROR);
                    $this->warn("error {$ch->name} {$air}");
                }
                else
                {
                    $ch->distribution_date = date('Y-m-d H:i:s');
                    $ch->save();
                    $this->info("save distribution date {$ch->name} {$air}");

                    if($is_today) $path = config('BVT_LIVE_PATH', false) ? config('BVT_LIVE_PATH').'\\'.BvtExporter::NAMES[$ch->name].'\\'.BvtExporter::NAMES[$ch->name].'.xml' : false;
                    else $path = config('BVT_XML_PATH', false) ? config('BVT_XML_PATH').'\\'.BvtExporter::NAMES[$ch->name].'_'.$air.'.xml': false; 
                    
                    if($path)
                        file_put_contents($path, $file);
                }
            }
            else {
                $this->warn("xml error {$ch->name} {$air}");
            }
        }
        
    }

    private function check($args)
    {

    }

    private function scan($args)
    {

    }
}
