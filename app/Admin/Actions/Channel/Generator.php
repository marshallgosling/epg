<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\ProgramsJob;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use Encore\Admin\Actions\RowAction;

class Generator extends RowAction
{
    public $name = '生成编单';

    public function handle(Channel $model)
    {
        // $model ...
        if($model->status != Channel::STATUS_EMPTY) {
            return $this->response()->error(__('Generator start failed message.'))->refresh();
        }

        if($model->audit_status == Channel::AUDIT_PASS) {
            return $this->response()->error(__('Generator start failed message.'))->refresh();
        }

        if($model->name == 'xkc') return $this->response()->warning(__('Generator closed.'))->refresh();

        
        $model->status = Channel::STATUS_WAITING;
        $model->save();

        ProgramsJob::dispatch($model->uuid)->onQueue('xkv');

        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

}