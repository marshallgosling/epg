<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Tools\ChannelGenerator;
use App\Tools\Material\MediaInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function PHPSTORM_META\elementType;

class fileTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:file {action?} {path?} {tags?}';

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
        $action = $this->argument('action') ?? "xml";
        

        if(in_array($action, ['import', 'clean', 'daily', 'compare', 'mediainfo']))
            $this->$action();
        

        //Material::upsert($items, ['unique_no'], ['frames', 'group', 'category','duration','md5','name']);
        //Material::insert($items);
        
        return 0;
    }

    private function mediainfo()
    {
        $file = $this->argument('path') ?? "";
        $material = Material::findOrFail($file);
        $unique_no = $material->unique_no;

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

                print_r($data);
    
                foreach(['records', 'record2', 'program'] as $table)
                    DB::table($table)->where('unique_no', $unique_no)->update($data);
            }
            else {
                foreach(['records', 'record2', 'program'] as $table)
                    DB::table($table)->where('unique_no', $unique_no)->update(['status'=>$status]);
            }
            
        }
        else {
            foreach(['records', 'record2', 'program','material'] as $table)
                DB::table($table)->where('unique_no', $unique_no)->update(['status'=>Material::STATUS_EMPTY]);
        }
    }

    private function compare()
    {
        $file = $this->argument('path') ?? "";
        if(!$file) return;
        $lines = explode(PHP_EOL, Storage::get($file));
        $succ = [];
        $miss = [];
        $erro = [];
        foreach($lines as $line)
        {
            
            $info = pathinfo(trim($line));
            if(array_key_exists('extension', $info) && $info['extension'] == 'mxf') {
                $filenames = explode('.', $info['filename']);

                if(count($filenames) == 2) {
                    $code = $filenames[1];
                    $m = Material::where('unique_no', $code)->first();
                    if($m) {
                        if($m->status == Material::STATUS_READY) {
                            $this->info("重复 ".$line);
                            continue;
                        }
                        $m->filepath = $line;
                        $m->status = Material::STATUS_READY;
                        $m->save();
                        $succ[] = $line;
                    }
                    else {
                        $miss[] = $line;
                    }
                }
                else {
                    $erro[] = $line;
                }
            }
        }
        Storage::put("result1.json", json_encode(compact('succ', 'miss', 'erro')));
    }

    private function daily()
    {
        // Copy xml files to CERTAIN folder
    }

    private function clean()
    {
        // Clean xml files 
    }

    private function loadEml($xml)
    {
        $item = [
            'name' => '', 'md5'=>'', 'duration'=>'', 'frames'=>0,'unique_no'=>'','category'=>'','group'=>''
        ];

        $names = (string) $xml->project[0]->children[0]->clip[0]->name[0];
        $durtion = (int) $xml->project[0]->children[0]->clip[0]->duration[0];

        $name = explode('.', $names)[0];
        $item['name'] = $name;
        $item['unique_no'] = explode('.', $names)[1];
        $item['channel'] = 'xkc';
        $item['frames'] = $durtion;
        $item['duration'] = ChannelGenerator::parseFrames($item['frames']);

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
        return $item;
    }

    private function loadObject($xml)
    {
        $item = [
            'name' => '', 'md5'=>'', 'duration'=>'', 'frames'=>0,'unique_no'=>'','category'=>'','group'=>''
        ];

        $attributes = $xml->Object[0]->attributes();
            if(!$attributes) return false;

            foreach($attributes as $name=>$attr)
            {
                //$this->info("$name : $attr");
                if($name == 'ObjectID') $item['unique_no'] = (string)$attr;
                if($name == 'OutPoint') $item['frames'] = (int)$attr;
                if($name == 'MD5') $item['md5'] = (string)$attr;
            }

            if($item['unique_no'] == 'XK0000000000000000') {
                $this->error("error xml: ".$item['unique_no'].' ignore');
                return false;
            }

            if($item['frames']<0) {
                $this->error("error frame: ".$item['frames'].' ignore');
                return false;
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

           
            $item['duration'] = ChannelGenerator::parseFrames($item['frames']);

            //$items[] = $item;

        return $item;
    }

    private function import()
    {
        $path = $this->argument('path') ?? "xml";
        $tags = $this->argument('tags') ?? "";

        $files = Storage::disk('data')->files($path, true);

        foreach($files as $f)
        {
            //$this->info("File name:".$f);
            if(!strpos($f, ".xml")) continue;
            $this->info("Process file name:".$f);
            
            $xml = simplexml_load_file(Storage::disk('data')->path($f));

            if(!$xml) continue;

            if($xml->Object) $item = $this->loadObject($xml);

            if($xml->project) $item = $this->loadEml($xml);

            //print_r($xml);exit;

            if(!$item) continue;
            if($tags != "") $item['category'] = $tags;
            

            if(! Material::where('unique_no', $item['unique_no'])->exists())
            {
                Material::create($item);
                //$items[] = $item;
                $this->info("json:".json_encode($item));
            }
            else {
                $this->error("File name:".$f);
            }
        }
    }
}
