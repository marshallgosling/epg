<?php

namespace App\Tools;

use App\Models\Channel;
use App\Models\Template;
use App\Models\ChannelPrograms;
use App\Models\Material;
use App\Models\Program;

class ChannelGenerator
{
    private $channel;
    private $templates;

    public function __construct()
    {
        //$this->channel = $channel;
    }

    public function loadTemplate($group='')
    {
        $this->templates = Template::where('group_id', 'default')->with('programs')->orderBy('sort', 'asc')->get();
    }

    public function generate(Channel $channel)
    {
        
        if(!$channel) {
            
            return ["satus" =>false, "message"=>"Channel is null"];
        }

        $last = strtotime($channel->air_date);

        foreach($this->templates as $t) {
            $air = strtotime($channel->air_date.' '.$t->start_at);

            if($air < $last) $air += 24 * 3600;

            $last = $air;

            $c = new ChannelPrograms();
            $c->name = $t->name;
            $c->schedule_start_at = $t->start_at;
            $c->schedule_end_at = $t->end_at;
            $c->channel_id = $channel->id;
            $c->start_at = date('Y-m-d H:i:s', $air);
            $c->duration = 0;
            $c->version = '1';
            
            $data = [];
            $programs = $t->programs()->get();
            foreach($programs as $p) {
                
                $item = Program::findRandom($p->category);
                //$item = Material::findRandom($p->category);

                if($item) {
                    
                    if($item->frames > 0) {
                        $data[] = $item->toArray();
                        $c->duration += $item->frames;                   
                    }
                    else {
                        $duration = $this->parseDuration($item->duration);
                        if($duration > 0) {
                            $data[] = $item->toArray();
                            $c->duration += $duration * config('FRAME', 25);
                        }  
                    }
                }
            }
            $c->data = $data;

            $c->save();


        }
    }

    private function parseDuration($str)
    {
        $duration = explode(':', $str);
        
        $seconds = count($duration )>= 3 ? (int)$duration[0]*3600 + (int)$duration[1]*60 + (int)$duration[2] : 0;

        return $seconds;
    }
}