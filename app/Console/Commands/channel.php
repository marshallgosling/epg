<?php

namespace App\Console\Commands;

use App\Models\ChannelPrograms;
use App\Models\Program;
use App\Models\Template;
use Carbon\Carbon;
use Illuminate\Console\Command;

class channel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:channel {group} {uuid}';

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
        $group = $this->argument('group') ?? "";
        $uuid = $this->argument('uuid') ?? "";

        $templates = Template::with('programs')->where('group_id', $group)->lazy();

        $channel = \App\Models\Channel::where('uuid', $uuid)->first();

        if(!$channel) {
            $this->error("Channel $uuid is null");
            return 0;
        }

        foreach($templates as $t) {
            $c = new ChannelPrograms();
            $c->name = $t->name;
            $c->schedule_start_at = $t->start_at;
            $c->schedule_end_at = $t->end_at;
            $c->channel_id = $channel->id;
            $c->start_at = $channel->air_date.' '.$t->start_at;
            $c->duration = 0;
            $c->version = '1';
            
            $data = [];
            $programs = $t->programs();
            foreach($programs as $p) {
                $c = $p->category;
                $item = Program::findOneOrderByRandom($c[0]);

                if($item) {
                    $data[] = $item->toArray();
                    $c->duration += (int)$item->duration;
                }
            }
            $c->data = $data;

            $c->save();
        }

        return 0;
    }
}
