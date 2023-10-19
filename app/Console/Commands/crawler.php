<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Material;
use App\Models\Program;
use App\Models\Spider\CnvSpider;
use Illuminate\Support\Facades\Storage;

class crawler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:crawler {url?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Maoch.com Crawler \n Support Program and Materials\n";
    
    protected $crawler;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->crawler = new CnvSpider();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = $this->argument('url');

        if($url == 'program') {
            $this->getPrograms();
        } 

        if($url == 'material') {
            $this->getMaterials();
        }

        if($url == 'cleanProgram') {
            Program::truncate();
            $this->info("clean Program data");
        }

        if($url == 'cleanMaterial') {
            Material::truncate();
            $this->info("clean Material data");
        }
        
        return 0;
    }


    private function login()
    {
        return $this->crawler->login('18001799001@163.com', '123QWE#canxin');
    }

    private function getMaterials()
    {
        if($this->login()) {
            $this->info("crawl Materials");
            $data = $this->crawler->getMeterials(1);
            $size = 1000;
            $id = 20000;
            $page = 0;
            $total = 0;
            while(true) {
                $this->info("Start at Page: $page, Total: $total");
                $data = $this->crawler->getMeterials($page, $size);

                if(count($data) == 0) break;
    
                foreach($data as &$p)
                {
                    $p['id'] = $id;
                    $id --;
                }

                Storage::disk('data')->put("material{$page}.js", json_encode($data));

                $this->info("batch insert Material to DB");

                Material::insert($data);

                if(count($data) < $size) break;
                $total += count($data);
                $page ++;
            }

            $this->info("End at Page: $page, Total: $total");
            //Meterial::insert($meterials);

        }
    }

    private function getPrograms()
    {
        if($this->login()) {
            $this->info("crawl Programs");
            $data = $this->crawler->getPrograms(1);
            $id = 20000;
            $size = 1000;
            $page = 0;
            $total = 0;
            while(true) {
                $this->info("Start at Page: $page, Total: $total");
                $data = $this->crawler->getPrograms($page, $size);

                if(count($data) == 0) break;
    
                foreach($data as &$p)
                {
                    $this->info("find Program Detail {$p['uuid']}");
                    $meta = $this->crawler->getProgramDetails($p['uuid']);
    
                    $p = array_merge($p, $meta);
                    $p['id'] = $id;
                    $id --;
                }

                Storage::disk('data')->put("program{$page}.js", json_encode($data));

                $this->info("batch insert Program to DB");
                    
                Program::insert($data);
    
                
                $page ++;

                if(count($data) < $size) break;
            }
            $this->info("End at Page: $page, Total: $total");

        }
    }
}
