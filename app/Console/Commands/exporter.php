<?php

namespace App\Console\Commands;

use App\Models\ExportJob;
use App\Tools\ExcelWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Tools\PHPExcel\Exception;
use Illuminate\Support\Str;

class exporter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:excel {jobid}';

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
        
        $export = ExportJob::findOrFail($id);

        $lines = \App\Tools\Exporter::gatherLines($export->start_at, $export->end_at);

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
        return 0;
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

}
