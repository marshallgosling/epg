<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\ProgramsJob;
use App\Jobs\Channel\RecordJob;
use App\Models\Channel;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class BatchGenerator extends BatchAction
{
    public $name = '批量生成编单';

    public function handle(Collection $collection)
    {
        //return $this->response()->success(__('Generator closed.'))->refresh();

        if(Storage::disk('data')->exists("generate_stall"))
        {
            return $this->response()->error(__('节目单自动生成工具遇到错误，需要人工干预.'))->refresh();
        }

        $group = '';
        foreach ($collection as $model) 
        {
            if($model->status != Channel::STATUS_EMPTY) {
                continue;
            }

            if($model->audit_status == Channel::AUDIT_PASS) {
                continue;
            }
            
            $model->status = Channel::STATUS_WAITING;
            $model->save();

            $group = $model->name;
            if($group == 'xkv')ProgramsJob::dispatch($model->uuid)->onQueue('xkv');
        }

        if($group == 'xkc')
            RecordJob::dispatch($group)->onQueue('xkc');
        
        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

}