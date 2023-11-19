<?php

namespace App\Console\Commands;

use App\Models\ChannelPrograms;
use App\Models\Program;
use App\Models\Template;
use App\Tools\ChannelGenerator;
use App\Tools\ProgramsExporter;
use Illuminate\Console\Command;

class channel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:channel {action?} {id?} {group?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create channel programs using template.';

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
        $group = $this->argument('group') ?? "default";
        $id = $this->argument('id') ?? "";
        $action = $this->argument('action') ?? "";

        if($action == 'list') $this->generateChannel($id, $group);

        if($action == 'export') $this->exportChannel($id);

        return 0;
    }

    private function exportChannel($id)
    {
        ProgramsExporter::generate($id);
        ProgramsExporter::exportXml(true);
    }

    private function generateChannel($id, $group='default')
    {
    
        $channel = \App\Models\Channel::find($id);

        if(!$channel) {
            $this->error("Channel $id is null");
            return 0;
        }

        $templates = Template::where('group_id', $group)->with('programs')->orderBy('sort', 'asc')->get();
        $last = strtotime($channel->air_date." 00:00:00");

        foreach($templates as $t) {
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

            $this->error("create program: {$t->name} {$t->start_at}");
            
            $data = [];
            $programs = $t->programs()->get();
            foreach($programs as $p) {
                
                $item = Program::findRandom($p->category);
                //$item = Material::findRandom($p->category);

                if($item) {
                    
                    if($item->frames > 0) {
                        $data[] = $item;
                        $c->duration += $item->frames;    
                        $cat = implode(',', $item->category);
                        $this->info("add item: {$cat} {$item->name} {$item->duration}");
                    }
                    else {

                        $this->warn(" {$item->name} no material found, so ignore.");
                        continue;

                        $duration = $this->parseDuration($item->duration);
                        if($duration > 0) {
                            $data[] = $item->toArray();
                            $c->duration += $duration * config('FRAME', 25);

                            $this->info("add item ( no material info ): {$item->name} {$item->duration}");
                        }  
                    }
                }
                else
                {
                    $this->error("category {$p->category} has no items.");
                }
            }
            $c->data = json_encode($data);

            $c->save();
            $this->info("save program.");

        }

        $channel->status = \App\Models\Channel::STATUS_READY;
        $channel->save();

        $this->info("Generate channel date: {$channel->air_date} succeed. "); 
    }

    private function parseDuration($str)
    {
        $duration = explode(':', $str);
        
        $seconds = count($duration )>= 3 ? (int)$duration[0]*3600 + (int)$duration[1]*60 + (int)$duration[2] : 0;

        return $seconds;
    }
}
