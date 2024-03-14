<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\XkvGeneratorJob;
use App\Models\Channel;
use App\Tools\Generator\XkvGenerator;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class BatchXkvGenerator extends BatchAction
{
    public $name = '批量生成编单';

    public function handle(Collection $collection)
    {
        //return $this->response()->success(__('Generator closed.'))->refresh();

        if(Storage::disk('data')->exists(XkvGenerator::STALL_FILE))
        {
            return $this->response()->error(__('V China 节目单自动生成工具遇到错误，需要人工干预.'))->refresh();
        }

        $group = '';
        foreach ($collection as $model) 
        {
            if($model->status != Channel::STATUS_EMPTY) {
                continue;
            }

            if($model->lock_status == Channel::LOCK_ENABLE) {
                continue;
            }
            
            $model->status = Channel::STATUS_WAITING;
            $model->save();

            XkvGeneratorJob::dispatch($model->uuid)->onQueue('xkv');
        }

        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

}