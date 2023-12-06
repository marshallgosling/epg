<?php

namespace App\Console\Commands;

use App\Events\Channel\CalculationEvent;
use App\Models\Category;
use App\Models\ChannelPrograms;
use App\Models\Channel;
use App\Models\Material;
use App\Tools\ChannelGenerator;
use App\Tools\Exporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $actions = ['import'];

        if(!in_array($action, $actions)) {
            $this->error("action param's value only supports ".implode(',', $actions));
            return 0;
        }

        $this->$action($id, $group);

        return 0;
    }

    private function import($id)
    {
        $models = Material::where('id', '>', $id)->get();
        foreach($models as $model)
        {
            $class = '\App\Models\Program';
            $program = new $class();
            
            if(in_array($model->category, ['tvshow', 'tvseries', 'movie','starmade','cartoon']))
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