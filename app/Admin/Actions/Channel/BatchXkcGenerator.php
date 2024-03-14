<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\XkcGeneratorJob;
use App\Models\Channel;
use App\Tools\Generator\XkcGenerator;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class BatchXkcGenerator extends BatchAction
{
    public $name = '批量生成编单';

    public function handle(Collection $collection)
    {
        //return $this->response()->success(__('Generator closed.'))->refresh();

        if(Storage::disk('data')->exists(XkcGenerator::STALL_FILE))
        {
            return $this->response()->error(__('节目单自动生成工具遇到错误，需要人工干预.'))->refresh();
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

            $group = $model->name;
            
        }

        XkcGeneratorJob::dispatch()->onQueue('xkc');
        
        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

}