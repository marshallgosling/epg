<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use App\Models\Template;
use App\Models\Material;
use App\Models\Program;
use App\Models\TemplatePrograms;
use App\Tools\CnvSpider;
use Illuminate\Support\Facades\Storage;

class crawler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:crawler {url?} {uuid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Maoch.com Crawler Support Program Template and Materials";
    
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
        $uuid = $this->argument('uuid') ?? "880159c-6265-4899-a4b3-adba2f18a3d1";

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

        if($url == 'cleanTemplate') {
            Template::truncate();
            $this->info("clean Template data");
        }

        if($url == "template") {
            $this->getTemplate($uuid);
        }

        if($url == 'templateProgram')
        {
            $this->getTemplateProgram($uuid);
        }

        if($url == "daily") {
            $this->daily();
        }
        
        return 0;
    }

    private function daily()
    {
        if($this->login()) {
            $this->info("crawl Materials");
            $data = $this->crawler->getMeterials(1);
            $data = array_reverse($data);
            $newdata = false;

            foreach($data as $item) {
                if (Material::where('unique_no', $item['unique_no'])->exists()) {
                    continue;
                }

                $this->info("find new Material: ".$item['unique_no']);
                Material::insert([$item]);
                $newdata = true;
            }

            if(!$newdata) $this->warn("no material found.");
            $newdata = false;

            $this->info("crawl Programs");
            $data = $this->crawler->getPrograms(1);

            $data = array_reverse($data);

            foreach($data as $p) {
                if (Program::where('unique_no', $p['unique_no'])->exists()) {
                    continue;
                }

                $this->info("find new Program: ".$p['unique_no']);
                $meta = $this->crawler->getProgramDetails($p['uuid']);
                $p = array_merge($p, $meta);

                Program::insert([$p]);
                $newdata = true;
            }

            if(!$newdata) $this->warn("no program found.");

        }
    }

    private function getTemplateProgram($uuid='')
    {
        $categories = false;
        if($this->login()) {
            
            //$uuid = $template->comment;
            $data = $this->crawler->getTemplatePrograms($uuid);

            $template = Template::where('comment', $uuid)->first();
            if(!$categories)$categories = Category::where('type', 'channel')->lazy()->pluck('duration', 'no')->toArray();

            $list = [];
            if(is_array($data)) {
                $this->info("find Template Programs:".$template->name);
                foreach($data as $idx=>$item)
                {
                    $item['template_id'] = $template->id;
                    $item['sort'] = $idx;
                    $item['type'] = (array_key_exists($item['category'], $categories) && $categories[$item['category']]) ? $categories[$item['category']] : '';
                    if($item['type'] == '0') {
                        $item['data'] = '';
                        $item['name'] = '';
                    }

                    if($item['type'] != '') {
                        $list[] = $item;
                    }
                }
            }
            else {
                $this->error("no programs founds.");
                return;
            }

            TemplatePrograms::where('template_id', $template->id)->delete();
            
            TemplatePrograms::insert($list);
            $template->version = $template->version + 1;
            $template->save();

            $this->info("Batch insert success. Total:".count($list));

        }
    }

    private function login()
    {
        $username = config('maoch_username') ?? '18001799001@163.com';
        $password = config('maoch_password') ?? '123QWE#canxin';
        return $this->crawler->login($username, $password);
    }

    private function getTemplate($uuid='')
    {
        if($this->login()) {
            $this->info("crawl Template");
            $data = $this->crawler->getTemplate($uuid);

            Template::insert($data);

            $this->info("Batch insert success.");
        }
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
                $total += count($data);

                if(count($data) < $size) break;
                
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
            $id = 11200;
            $size = 200;
            $page = 44;
            $total = 0;
            while(true) {
                $this->info("Start at Page: $page, Total: $total");
                $data = $this->crawler->getPrograms($page, $size);

                if(count($data) == 0) break;
    
                foreach($data as &$p)
                {
                    //$this->info("find Program Detail {$p['uuid']}");
                    $p['id'] = $id;
                    $id --;

                    $meta = $this->crawler->getProgramDetails($p['uuid']);
                    
                    /*if(!$meta) {
                        $this->error("Errors at getting Program Detail {$p['uuid']}");
                        continue;
                    }*/
                    $p = array_merge($p, $meta); 
                    
                }

                Storage::disk('data')->put("program{$page}.js", json_encode($data));

                $this->info("batch insert Program to DB");
                    
                Program::insert($data);
          
                $total += count($data);
                $page ++;

                if(count($data) < $size) break;
            }
            $this->info("End at Page: $page, Total: $total");

        }
    }
}
