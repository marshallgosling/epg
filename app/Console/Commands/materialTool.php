<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\Notification;
use App\Models\Program;
use App\Models\Record;
use App\Tools\ChannelGenerator;
use App\Tools\Material\MediaInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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

        $actions = ['import','move', 'seconds','mediainfo'];

        if(!in_array($action, $actions)) {
            $this->error("action param's value only supports ".implode(',', $actions));
            return 0;
        }

        $this->$action($id, $group);

        return 0;
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

    private function import($id)
    {
        $models = Material::where('id', '>', $id)->get();
        foreach($models as $model)
        {
            $class = '\App\Models\Program';
            $program = new $class();
            
            if(in_array($model->category, ['Entertainm', 'drama', 'movie','CanXin','cartoon']))
            {
                $class = '\App\Models\Record';
                $program = new $class();
                $program->episodes = $model->group;
                if(preg_match('/(\d+)$/', $model->name, $matches))
                {
                    $program->ep = (int) $matches[1];
                }
            }
            
            $program->name = $model->name;
            $program->unique_no = $model->unique_no;
            $program->duration = $model->duration;
            $program->category = [$model->category];
            
            if($class::where('unique_no', $model->unique_no)->exists())
            {
                continue;
            }
            else {
                $program->save();
            }
        }
    }

    
}
