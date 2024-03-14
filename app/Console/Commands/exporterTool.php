<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\ExportList;
use App\Models\Notification;
use App\Tools\Exporter\ExcelWriter;
use App\Tools\Exporter\BvtExporter;
use App\Tools\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Nathan\PHPExcel\Exception;
use Illuminate\Support\Str;

class exporterTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:export {action?} {id?} {data?}';

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
        $data = $this->argument('data') ?? false;
        $action = $this->argument('action') ?? "";
        
        if(in_array($action, ['excel', 'xml', 'test', 'testxml', 'simplexml'])) {
            $this->$action($id, $data);
        }
        
        return 0;
    }

    private function test($id, $data)
    {
        $d = DB::table('notification')->selectRaw("`type`, count(`type`) as total")->where('viewed', 0)->groupBy('type')->pluck('total', 'type')->toArray();
        print_r($d);
    }

    private function xml($id, $date=false)
    {
        $channel = Channel::findOrFail($id);

        $data = BvtExporter::collectData($channel->air_date, $channel->name);

        BvtExporter::generateData($channel, $data, $date);
        BvtExporter::exportXml();

        $fake = $date ? " -> $date":"";
        Notify::fireNotify(
            $channel->name,
            Notification::TYPE_XML, 
            "生成 XML {$channel->air_date} {$fake} 成功. ", 
            "",
            Notification::LEVEL_INFO
        );
    }

    private function testxml($id, $date)
    {
        $programs = ChannelPrograms::where('channel_id', 0)->orderBy('sort')->get();
        $data = [];
        $order = [];
        foreach($programs as $p) {
            $order[] = $p->id;
            $data[$p->id] = $p->toArray();
            $data[$p->id]['items'] = json_decode($p->data, true);
        }
        $data['order'] = $order;

        $channel = new Channel();
        $channel->id = $id;
        $channel->name = 'xkv';
        $channel->air_date = $date;

        $json = BvtExporter::generateData2($channel, $data);

        //print_r($json);

        BvtExporter::exportXml(false, 'test');
    }

    private function simplexml($id, $date)
    {
        $programs = ChannelPrograms::where('channel_id', 0)->orderBy('sort')->get();
        
        //$order = [];
        foreach($programs as $p) {
            $data = json_decode($p->data);
        }
        //$data['order'] = $order;

        $channel = new Channel();
        $channel->id = $id;
       
        $channel->status = Channel::STATUS_READY;
        $channel->name = 'xkv';
        $channel->air_date = $date;

        $json = BvtExporter::generateSimple($channel, $data);

        //print_r($json);

        BvtExporter::exportXml(false, 'test');
    }

    private function excel($id, $p=false)
    {
        $export = ExportList::findOrFail($id);

        $lines = BvtExporter::gatherLines($export->start_at, $export->end_at, $export->group_id);

        if(count($lines) == 0) {
            $export->status = ExportList::STATUS_ERROR;
            $export->reason = "串联单数据为空";
            $export->save();
            return;
        }

        $filename = $export->group_id.'_'.$export->start_at .'_'. $export->end_at.'_'. Str::random(4).'.xlsx';

        try {
            $this->printToExcel($lines, $filename, $export->group_id);
            $export->status = ExportList::STATUS_READY;
            $export->filename = $filename;
            $export->save();
            Notify::fireNotify(
                $export->group_id,
                Notification::TYPE_EXCEL, 
                "生成 Excel {$export->name} 成功. ", 
                "文件名:{$filename}",
                Notification::LEVEL_INFO
            );
        }catch(Exception $e)
        {
            $export->status = ExportList::STATUS_ERROR;
            $export->reason = $e->getMessage(); 
            $export->save();
            Notify::fireNotify(
                $export->group_id,
                Notification::TYPE_EXCEL, 
                "生成 Excel {$export->name} 失败. ", 
                "保存节目串联单数据出错，Excel模版错误或磁盘读写错误。文件名:{$filename}",
                Notification::LEVEL_WARN
            );
        }
    }

    private function printToExcel($data, $filename, $disk)
    {
        $filename = Storage::disk($disk)->path($filename);

        $data[] = [
            '', '©2023 - '. date('Y'),	'软件节目编单系统',	'星空传媒', '', '', '', '', '', ''
        ];

        ExcelWriter::loadTemplate(Storage::path(config('EXCEL_TEMPLATE', 'channelv.xlsx')));

        ExcelWriter::printData($data, config('EXCEL_OFFSET', 10));

        ExcelWriter::outputFile($filename, 'file');
    }

    private function material()
    {
        
    }

}
