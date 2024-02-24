<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\ExportList;
use App\Models\Notification;
use Illuminate\Support\Facades\Storage;
use App\Tools\Exporter\BvtExporter;
use App\Tools\ExcelWriter;
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

class ExportJob implements ShouldQueue, ShouldBeUnique
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
        $this->log_channel = 'export';
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
        $channel = Channel::findOrFail($this->id);

        $data = BvtExporter::collectData($channel->air_date, $channel->name);

        BvtExporter::generateData($channel, $data);
        BvtExporter::exportXml();

        Notify::fireNotify(
            $channel->name,
            Notification::TYPE_XML, 
            "生成 XML {$channel->air_date} 成功. ", 
            "",
            Notification::LEVEL_INFO
        );             
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
