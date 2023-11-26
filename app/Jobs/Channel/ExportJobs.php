<?php

namespace App\Jobs\Channel;

use App\Models\ExportJob;
use Illuminate\Support\Facades\Storage;
use App\Tools\ProgramsExporter;
use App\Tools\ExcelWriter;
use App\Tools\PHPExcel\Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ExportJobs implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $export = ExportJob::findOrFail($this->id);

        $lines = ProgramsExporter::gatherLines($export->start_at, $export->end_at);

        if(count($lines) == 0) {
            $export->status = ExportJob::STATUS_ERROR;
            $export->reason = "串联单数据为空";
            $export->save();
            return;
        }

        $filename = $export->group_id.'_'.$export->start_at .'_'. $export->end_at.'_'. Str::random(4).'.xlsx';

        try {
            $this->printToExcel($lines, $filename);
            $export->status = ExportJob::STATUS_READY;
            $export->filename = $filename;
            $export->save();
        }catch(Exception $e)
        {
            $export->status = ExportJob::STATUS_ERROR;
            $export->reason = $e->getMessage(); 
            $export->save();
        }
             
    }

    private function printToExcel($data, $filename)
    {
        $filename = Storage::disk('public')->path($filename);

        $data[] = [
            '', '©2023 - '. date('Y'),	'软件节目编单系统',	'星空传媒', '', '', '', '', '', ''
        ];

        ExcelWriter::loadTemplate(Storage::path(config('EXCEL_TEMPLATE', 'channelv.xlsx')));

        ExcelWriter::printData($data, config('EXCEL_OFFSET', 10));

        ExcelWriter::ourputFile($filename, 'file');
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
