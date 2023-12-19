<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ExportList;
use App\Models\Notification;
use App\Tools\ExcelWriter;
use App\Tools\Exporter;
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
    protected $signature = 'tools:export {action?} {id?}';

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
        $start_at = '2023-12-31';
        $end_at = '2023-12-31';

        $id = $this->argument('id') ?? "";
        $action = $this->argument('action') ?? "";
        
        if(in_array($action, ['excel', 'xml', 'test'])) {
            $this->$action($id);
        }
        
        return 0;
    }

    private function test()
    {
        $d = DB::table('notification')->selectRaw("`type`, count(`type`) as total")->where('viewed', 0)->groupBy('type')->pluck('total', 'type')->toArray();
        print_r($d);
    }

    private function xml_old($id)
    {
        
        //Exporter::generate($id);
        $channel = Channel::findOrFail($id);
        $data = Exporter::gatherData($channel->air_date, $channel->name, Exporter::TIMES[$channel->name]);
   
        Exporter::generate($id);
        Exporter::exportXml();
    
        Notify::fireNotify(
            $channel->name,
            Notification::TYPE_XML, 
            "生成 XML {$channel->air_date} 成功. ", 
            "",
            Notification::LEVEL_INFO
        );
    }

    private function xml($id)
    {
        $channel = Channel::findOrFail($id);

        $data = Exporter::gatherData($channel->air_date, $channel->name);

        Exporter::generateData($channel, $data);
        Exporter::exportXml();

        Notify::fireNotify(
            $channel->name,
            Notification::TYPE_XML, 
            "生成 XML {$channel->air_date} 成功. ", 
            "",
            Notification::LEVEL_INFO
        );
    }

    private function excel($id)
    {
        $export = ExportList::findOrFail($id);

        $lines = Exporter::gatherLines($export->start_at, $export->end_at, $export->group_id);

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

}
