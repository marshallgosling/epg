<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Meterial;
use App\Models\Program;
use App\Models\Spider\CnvSpider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class generateTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:generate';

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
        //$s = '<input name="__RequestVerificationToken" type="hidden" value="03rLo1seSzP9Ot2klX2-8HKRPRoR86BTK65CiXGAH_d5SiVCpwrVin4wQXGHwmcNQrKEru_iBVm73jFDuA62w4zXB-xYAuao90iMiO87kZ81" />';
        //$m = preg_match('/value=\"([\w\-]+)\" \/>/', $s, $match);

        //Meterial::truncate();

        $this->getPrograms();

        return 0;
    }

    private function getPrograms()
    {
        $spider = new CnvSpider();
        $r = $spider->login('18001799001@163.com', '123QWE#canxin');

        if($r) {
            echo "find Programs\n";
            $data = $spider->getPrograms(1);
            
            $data = $spider->getPrograms(0, 100);

            $meterials = array_reverse($data);

            Program::insert($meterials);

        }
    }

    private function getMeterials()
    {
        $spider = new CnvSpider();
        $r = $spider->login('18001799001@163.com', '123QWE#canxin');

        if($r) {
            echo "find Programs\n";
            $data = $spider->getPrograms(1);
            
            $data = $spider->getPrograms(0, 100);

            $meterials = array_reverse($data);

            //Meterial::insert($meterials);

        }
    }

}