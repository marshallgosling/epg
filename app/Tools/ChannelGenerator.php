<?php

namespace App\Tools;

use App\Models\Channel;
use App\Models\Template;
use App\Models\ChannelPrograms;
use App\Models\Material;
use App\Models\Program;
use Illuminate\Support\Facades\Log;
class ChannelGenerator
{
    private $channel;
    private $templates;
    private $logs;

    public function __construct()
    {
        
    }

    public function loadTemplate($channel, $group='default')
    {
        $this->channel = $channel;
        $this->templates = Template::where('group_id', $group)->with('programs')->orderBy('sort', 'asc')->get();
    }

    public function generate(Channel $channel)
    {
        $logs = [];
        if(!$channel) {
            
            return ["satus" =>false, "message"=>"Channel is null"];
        }

        $air = strtotime($channel->air_date." 06:00:00");

        foreach($this->templates as $t) {
            
            $c = new ChannelPrograms();
            $c->name = $t->name;
            $c->schedule_start_at = $t->start_at;
            $c->schedule_end_at = $t->end_at;
            $c->channel_id = $channel->id;
            $c->start_at = date('Y-m-d H:i:s', $air);
            $c->duration = 0;
            $c->version = '1';

            $this->error("create program: {$t->name} {$t->start_at}");
            
            $data = [];
            $programs = $t->programs()->get();
            foreach($programs as $p) {
                
                if($p->data != '') {
                    $item = Program::findUnique($p->data);
                }
                
                if(!$item)
                    $item = Program::findRandom($p->category);

                if($item) {
                    
                    if($item->frames > 0) {
                        $seconds = $this->parseDuration($item->duration);
                        $air += $seconds;
                        $c->duration += $seconds;   
                        $data[] = $item; 
                        $cat = implode(',', $item->category);
                        $this->info("add item: {$cat} {$item->name} {$item->duration}");
                    }
                    else {

                        $this->warn(" {$item->name} no material found, so ignore.");

                    }
                }
                else
                {
                    $this->error("category {$p->category} has no items.");
                }
            }
            $c->data = json_encode($data);
            $c->end_at = date('Y-m-d H:i:s', $air);
            $c->save();

        }
    }

    private function parseDuration($str)
    {
        $duration = explode(':', $str);
        
        $seconds = count($duration )>= 3 ? (int)$duration[0]*3600 + (int)$duration[1]*60 + (int)$duration[2] : 0;

        return $seconds;
    }

    protected function info($msg)
    {
        $this->log($msg, 'info');
    }

    protected function warn($msg)
    {
        $this->log($msg, 'warn');
    }

    protected function error($msg)
    {
        $this->log($msg, 'error');
    }

    private function log($msg, $level="info")
    {
        $msg = date('Y/m/d H:i:s ') . "$level: " . $msg;
        echo $msg.PHP_EOL;
        Log::channel('channel')->error($msg);
    }
}