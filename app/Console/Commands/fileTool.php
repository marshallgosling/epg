<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Tools\ChannelGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class fileTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:file {path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process xml metadata';

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
        $path = $this->argument('path') ?? "xml";

        $files = Storage::disk('data')->files($path);

        $items = [];

        foreach($files as $f)
        {
            $this->info("File name:".$f);

            $item = [
                'name' => '', 'md5'=>'', 'duration'=>'', 'frames'=>0,'unique_no'=>'','category'=>'','group'=>''
            ];

            $xml = simplexml_load_file(Storage::disk('data')->path($f));

            $attributes = $xml->Object[0]->attributes();
            if(!$attributes) continue;

            foreach($attributes as $name=>$attr)
            {
                //$this->info("$name : $attr");
                if($name == 'ObjectID') $item['unique_no'] = (string)$attr;
                if($name == 'OutPoint') $item['frames'] = (int)$attr;
                if($name == 'MD5') $item['md5'] = (string)$attr;
            }

            if($item['unique_no'] == 'XK0000000000000000') {
                $this->error("error xml: ".$item['unique_no'].' ignore');
                continue;
            }

            if($item['frames']<0) {
                $this->error("error frame: ".$item['frames'].' ignore');
                continue;
            }

            $name = (string) $xml->Object[0]->MetaData[0]->sAttribute[0];
            $item['name'] = $name;

            

            if(preg_match('/(\d+)$/', $name, $matches))
            {
                $ep = (int) $matches[1];
                if($ep > 1000) {
                    $item['category'] = 'tvshow';
                }
                else {
                    $item['category'] = 'tvseries';
                    $group = str_replace($matches[1], "", $name);
                    $item['group'] = trim(trim($group), '-');
                }

                
            }
            else {
                $item['category'] = 'movie';
            }


            
            $item['duration'] = ChannelGenerator::parseFrames($item['frames']);

            $items[] = $item;
        }

        Material::upsert($items, ['unique_no'], ['frames', 'group', 'category','duration','md5','name']);
   
        
        return 0;
    }
}
