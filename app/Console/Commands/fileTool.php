<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Tools\ChannelGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

use function PHPSTORM_META\elementType;

class fileTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:file {path?} {tags?}';

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
        $tags = $this->argument('tags') ?? "";

        $files = Storage::disk('data')->files($path, true);

        $items = [];

        foreach($files as $f)
        {
            //$this->info("File name:".$f);
            if(!strpos($f, ".xml")) continue;

            $item = [
                'name' => '', 'md5'=>'', 'duration'=>'', 'frames'=>0,'unique_no'=>'','category'=>'','group'=>''
            ];

            $xml = simplexml_load_file(Storage::disk('data')->path($f));

            if(!$xml || !$xml->Object) continue;

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
            $item['channel'] = 'xkc';
            
            if(preg_match('/(\d+)$/', $name, $matches))
            {
                $ep = (int) $matches[1];
                if($ep > 1000) {
                    $item['category'] = 'Entertainm';
                }
                else {
                    if($item['frames']<30000)
                        $item['category'] = 'cartoon';
                    else if($item['frames'] > 90000)
                        $item['category'] = 'CanXin';
                    else
                        $item['category'] = 'drama';
                    //$group = str_replace($matches[1], "", $name);
                    
                }

                $group = preg_replace('/(\d+)$/', "", $name);
                $item['group'] = trim(trim($group), '_-');
            }
            else {
                $item['category'] = 'movie';
            }

            if($tags != "") $item['category'] = $tags;
            
            $item['duration'] = ChannelGenerator::parseFrames($item['frames']);

            //$items[] = $item;

            if(! Material::where('unique_no', $item['unique_no'])->exists())
            {
                Material::create($item);
                //$items[] = $item;

            }
            else {
                $this->error("File name:".$f);
            }

        }

        //Material::upsert($items, ['unique_no'], ['frames', 'group', 'category','duration','md5','name']);
        //Material::insert($items);
        
        return 0;
    }
}
