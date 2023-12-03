<?php

namespace App\Console\Commands;

use App\Tools\Statistic\StatisticProgram;
use Illuminate\Console\Command;

class statisticTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:statistic {model} {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $id = $this->argument('id') ?? "";
        $model = $this->argument('model') ?? "";

        $statistic = new StatisticProgram();
        $statistic->load();

        //print_r($statistic->channels->toArray());

        $results = $statistic->scan();

        print_r($results);

        $statistic->print();

        $statistic->store();


        return 0;
    }
}
