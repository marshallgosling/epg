<?php

namespace App\Console\Commands;

use App\Jobs\Material\ScanFolderJob;
use App\Models\Channel;
use App\Models\Material;
use App\Models\Notification;
use App\Models\Program;
use App\Models\Record;
use App\Tools\ChannelGenerator;
use App\Tools\Exporter\ExcelWriter;
use App\Tools\Material\MediaInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class materialTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:material {action?} {id?} {group?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import material to programs.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $group = $this->argument('group') ?? "xkv";
        $id = $this->argument('id') ?? "";
        $action = $this->argument('action') ?? "";

        $actions = ['import','move', 'seconds','mediainfo','export','ep','folder'];

        if(!in_array($action, $actions)) {
            $this->error("action param's value only supports ".implode(',', $actions));
            return 0;
        }

        $this->$action($id, $group);

        return 0;
    }

    private function folder($id, $action='')
    {
        $scan = new ScanFolderJob($id, $action);
        $scan->handle();
    }

    private function ep()
    {
        $list = Material::where('channel', 'xkc')->where('ep', 1)->get();
        foreach($list as $m)
        {
            if(preg_match('/(\d+)$/', $m->name, $matches))
            {
                $m->ep = (int) $matches[1];
                $m->save();
            }
            
        }
    }

    private function export($status=Material::STATUS_EMPTY, $group=false)
    {
        $data = [];
        
        $materials = DB::table('material')->where('status', $status)->get();
        foreach($materials as $m)
        {
            $data[] = [
                Channel::GROUPS[$m->channel], $m->unique_no, $m->name, $m->duration, $m->category
            ];
        }

        $filename = Storage::path('material.xlsx');

        ExcelWriter::initialExcel('素材列表');
        ExcelWriter::setupColumns(['频道','播出编号','名称','时长','分类']);

        ExcelWriter::printData($data, 2);

        ExcelWriter::outputFile($filename, 'file');
    }

    private function mediainfo($id, $group=0)
    {
        
        $material = Material::findOrFail($id);

        if(file_exists($material->filepath)) {
            try{
                $info = MediaInfo::getRawInfo($material);
            }catch(\Exception $e)
            {
                $info = false;
            }

            echo $info;
        }
    }

    private function seconds() {
        $items = Program::where('seconds', 0)->select('id','duration','seconds')->get();
        foreach($items as $item)
        {
            $item->seconds = ChannelGenerator::parseDuration($item->duration);
            $item->save();
        }
    }

    private function notify() {
        
        $data = ['total'=>(int)Cache::get('notify_total')];
        foreach(Notification::TYPES as $type)
        {
            $data[$type] = (int)Cache::get("notify_$type");
            $this->info("notify_$type : ".$data[$type]);
        }
        
        $type = 'total';
        $this->info("notify_$type : ".$data[$type]);
        
    }

    private function move($id)
    {
        $models = Program::where('id', '>', $id)->get();
        foreach($models as $model)
        {
            $record = new Record();
            $record->name = $model->name;
            $record->unique_no = $model->unique_no;
            $record->duration = $model->duration;
            $record->category = $model->category;
            if(Record::where('unique_no', $model->unique_no)->exists())
            {
                continue;
            }
            else {
                $record->save();
            }
        }
    }

    private function import($table='record')
    {
        $models = Material::whereRaw('`channel`=? and `status`=? and `unique_no` not in (select `unique_no` from `'.$table.'`)',['xkc', Material::STATUS_READY])->orderBy('id', 'asc')->lazy();

        foreach($models as $model)
        {
            $class = '\App\Models\Record';
            $program = new $class();
            
            //if(in_array($model->category, ['Entertainm', 'drama', 'movie','CanXin','cartoon']))
            {
                // $class = '\App\Models\Record';
                // $program = new $class();
                $program->episodes = $model->group;
                $program->ep = $model->ep;
                
            }
            $program->status = Record::STATUS_READY;
            $program->name = $model->name;
            $program->unique_no = $model->unique_no;
            $program->duration = $model->duration;
            $program->category = [$model->category];
            $program->seconds = ChannelGenerator::parseDuration($model->duration);
            
            // if($class::where('unique_no', $model->unique_no)->exists())
            // {
            //     continue;
            // }
            // else {
                $program->save();
            //}
            break;
        }
    }

    
}
