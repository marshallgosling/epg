<?php

namespace App\Jobs;

use App\Jobs\Material\MediaInfoJob;
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
        $channel = Channel::find($this->id);
        if(!$channel) return;

        //$data = BvtExporter::collectData($channel->air_date, $channel->name);
        $data = BvtExporter::collectEPG($channel);

        if(count($data) <= 10) {
            Notify::fireNotify(
                $channel->name,
                Notification::TYPE_XML, 
                "生成 XML {$channel->air_date} 失败. ", 
                "未能找到合适的时间锚点(00:00:00 ～ 24:00:00)，大概率原因是编单时间不对（时间和 17:00 差距过大）",
                Notification::LEVEL_ERROR
            );
            $channel->comment = "生成播出编单失败，请检查编单结束时间（和 17:00 差距大于30分钟以上）";
            $channel->status = Channel::STATUS_ERROR;
            $channel->save();
        }
        BvtExporter::generateData($channel, $data);
        BvtExporter::exportXml($channel->name);

        if($channel->status == Channel::STATUS_DISTRIBUTE) {
            //MediaInfoJob::dispatch($channel->id, 'distribute')->onQueue('media');
        }

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
