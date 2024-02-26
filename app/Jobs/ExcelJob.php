<?php

namespace App\Jobs;

use App\Models\ExportList;
use App\Models\Notification;
use Illuminate\Support\Facades\Storage;
use App\Tools\Exporter\BvtExporter;
use App\Tools\Exporter\ExcelWriter;
use App\Tools\Exporter\TableGenerator;
use App\Tools\LoggerTrait;
use App\Tools\Notify;
use Nathan\PHPExcel\Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ExcelJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggerTrait;

    // Job ID;
    private $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->log_channel = 'excel';
        $this->log_print = false;
    }

    public function uniqueId()
    {
        return $this->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $export = ExportList::findOrFail($this->id);

        $this->info('导出Excel串联单任务: '.$export->name.' 日期: '.$export->start_at .' '.$export->end_at);

        if($export->type == ExportList::TYPE_NORMAL)
        {
            $this->processNormal($export);
        }

        if($export->type == ExportList::TYPE_HK)
        {
            $this->processHK($export);
        }
    }

    private function processHK($export)
    {
        $generator = new TableGenerator($export->group_id);
        $st = strtotime($export->start_at);
        $ed = strtotime($export->end_at);
        $days = $generator->generateDays($st, $ed);
        $data = $generator->processData($days);
        $template = $generator->loadTemplate();
        $table = $generator->export($days, $template, $data);
        
    }

    private function processNormal($export)
    {
        $lines = BvtExporter::gatherLines($export->start_at, $export->end_at, $export->group_id);

        if(count($lines) == 0) {
            $export->status = ExportList::STATUS_ERROR;
            $export->reason = "串联单数据为空";
            $export->save();
            $this->warn('节目串联单数据为空，直接退出。');

            Notify::fireNotify(
                $export->group_id,
                Notification::TYPE_EXCEL, 
                "生成 Excel {$export->name} 失败. ", 
                "{$export->start_at} {$export->end_at} 节目串联单数据为空，直接退出。",
                Notification::LEVEL_WARN
            );

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
            $this->error("保存节目串联单数据出错，Excel模版错误或磁盘读写错误。文件名:{$filename}\n错误:".$e->getMessage());

            Notify::fireNotify(
                $export->group_id,
                Notification::TYPE_EXCEL, 
                "生成 Excel {$export->name} 失败. ", 
                "保存节目串联单数据出错，Excel模版错误或磁盘读写错误。文件名:{$filename}",
                Notification::LEVEL_WARN
            );
        }

        
             
    }

    private function printToExcel($data, $filename, $disk='excel')
    {
        $filename = Storage::disk($disk)->path($filename);

        $data[] = [
            '', '©2023 - '. date('Y'),	'软件节目编单系统',	'星空传媒', '', '', '', '', '', ''
        ];

        ExcelWriter::loadTemplate(Storage::path(config('EXCEL_TEMPLATE', 'channelv.xlsx')));

        ExcelWriter::printData($data, config('EXCEL_OFFSET', 10));

        ExcelWriter::outputFile($filename, 'file');
    }

    /**
     * Get the cache driver for the unique job lock.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function uniqueVia()
    {
        return Cache::driver('redis');
    }
}
