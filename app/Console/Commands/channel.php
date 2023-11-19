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

        $generator = new ChannelGenerator();
        $generator->loadTemplate($group);

        $generator->generate($channel);

        $channel->status = \App\Models\Channel::STATUS_READY;
        $channel->save();

        $this->info("Generate programs date: {$channel->air_date} succeed. "); 
    }
}
