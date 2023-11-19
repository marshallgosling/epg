<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ChannelPrograms;
use App\Models\Meterial;
use App\Models\Program;
use App\Models\Template;
use App\Tools\ChannelFixer;
use App\Tools\CnvSpider;
use App\Tools\ProgramsExporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class generateTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:generate {id?}';

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

        $id = $this->argument('id') ?? "";

        $body = Storage::disk('data')->get('sample.html');

        $spider = new CnvSpider();
        $items = $spider->parseTemplatePrograms($body);

        print_r($items);

        return 0;
        
        // $template = Template::findOrFail($id);

        // $programs = $template->programs()->get();

        // print_r($programs->toArray());
        
        //$this->getPrograms();

        $jsonstr = Storage::disk('data')->get('template.json');

        $json = json_decode($jsonstr);

        $channel = \App\Models\Channel::find($id);

        $json->ChannelName = $channel->name;
        $json->PgmDate = $channel->air_date;
        $json->Version = $channel->version;

        $programs = $channel->programs()->get();

        //$json->Count = count($programs);

        foreach($programs as $idx=>$program)
        {
            $date = Carbon::parse($program->start_at);
            // if not exist, just copy one 
            if(!array_key_exists($idx, $json->ItemList)) {
                $json->ItemList[] = clone $json->ItemList[$idx-1];
                $cl = [$json->ItemList[$idx]->ClipsItem[0]];
                $json->ItemList[$idx]->ClipsItem = $cl;
            }

            $itemList = &$json->ItemList[$idx];

            $start = ChannelPrograms::caculateFrames($date->format('H:i:s'));
                       
                $itemList->StartTime = $start;
                $itemList->SystemTime = $date->format('Y-m-d H:i:s');
                $itemList->Name = $program->name;
                $itemList->BillType = $date->format('md').'新建';
                $itemList->LimitLen = ChannelPrograms::caculateFrames($program->duration);
                $itemList->PgmDate = $date->diffInDays(Carbon::parse('1899-12-30 00:00:00'));

            $clips = &$itemList->ClipsItem;
            $data = json_decode($program->data);
            $duration = 0;
            if(is_array($data)) foreach($data as $n=>$clip)
            { 
                if(!array_key_exists($n, $clips)) $clips[$n] = clone $clips[$n-1];
                
                $c = &$clips[$n];
                $c->FileName = $clip->unique_no;
                $c->Name = $clip->name;
                $c->Id = $clip->unique_no;
                $c->LimitDuration = ChannelPrograms::caculateFrames($clip->duration);
                $c->Duration = ChannelPrograms::caculateFrames($clip->duration);
                

                $duration += ChannelPrograms::caculateSeconds($clip->duration);
            }
            $itemList->Length = $duration * config('FRAME', 25);
            $itemList->LimitLen = $duration * config('FRAME', 25);
            $itemList->ID = (string)Str::uuid();
            $itemList->Pid = (string)Str::uuid();
            $itemList->ClipsCount = count($data);
            //$itemList->ClipsItem = $clips;
            //$json->ItemList[$idx] = $itemList;
            //break;
        }

        //$json->ItemList[$idx] = $itemList;

        //echo json_encode($json);

        $exporter = new \App\Tools\XmlExporter();
        $xml = $exporter->export($json, 'PgmItem');

        Storage::disk('public')->put($json->ChannelName.'_'.$json->PgmDate.'.xml', $xml);


        return 0;
    }

    private function getTemplatePrograms()
    {
        $spider = new CnvSpider();
        
        
        
        $r = $spider->login('18001799001@163.com', '123QWE#canxin');

        if($r) {
            echo "find Programs\n";
            $data = $spider->getPrograms(1);

        }
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