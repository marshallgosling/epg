<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ChannelPrograms;
use App\Models\Meterial;
use App\Models\Program;
use App\Models\Template;
use App\Tools\ChannelFixer;
use App\Tools\CnvSpider;
use App\Tools\ProgramsExporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class generateTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:generate {id?} {time?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id') ?? "xkc_generator_14_20240326164244.json";
        $time = $this->argument('time') ?? "";

        $file = Storage::get($id);
        $json = json_decode($file);

        


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
    
}