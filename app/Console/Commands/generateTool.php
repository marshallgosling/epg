<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Meterial;
use App\Models\Program;
use App\Models\Spider\CnvSpider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Nette\Utils\FileSystem;

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
        $arr = ['B1','C1','RK','IDOL','B1','B1','B1','B1'];

        //foreach($arr as $c) {
        for($i=0;$i<10000;$i++) {
            $item = Program::findOneOrderByRandom('B1');
            $this->info("id: {$item->id} {$item->name} ");
        }

        echo 'B1: '.Program::getTotal('B1');
        
        //$this->getPrograms();

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

            //$meterials = array_reverse($data);
            $raw = [];

            foreach($data as &$p)
            {
                echo "find Program Detail {$p['uuid']}\n";
                $meta = $spider->getProgramDetails($p['uuid']);

                echo "update Mete {$p['unique_no']}:".json_encode($meta)."\n";
                Program::where('unique_no', $p['unique_no'])->update($meta);

                $p['meta'] = $meta;
            }

            Storage::disk('data')->put('samples.js', json_encode($data));

            //print_r($data);

            //Program::insert($meterials);

        }
    }

    private function getProgramDetail($uuid)
    {
        $spider = new CnvSpider();
        $r = $spider->login('18001799001@163.com', '123QWE#canxin');

        if($r) {
            echo "find Program Details\n";
            $data = $spider->getProgramDetails($uuid);
            
            //$data = $spider->getPrograms(0, 100);

            //$meterials = array_reverse($data);

            print_r($data);

            //Program::insert($meterials);

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
    

    private $data = <<<DATA
    ddd;
    DATA;
}