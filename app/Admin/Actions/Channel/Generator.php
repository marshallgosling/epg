<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\ProgramsJob;
use App\Models\Channel;
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

        ProgramsJob::dispatch($model->uuid);
        $model->status = Channel::STATUS_RUNNING;
        $model->save();

        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

}