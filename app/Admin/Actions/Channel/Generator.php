<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\XkvGeneratorJob;
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

        if($model->lock_status == Channel::LOCK_ENABLE) {
            return $this->response()->error(__('Generator start failed message.'))->refresh();
        }

        if($model->name == 'xkc') return $this->response()->warning(__('Generator closed.'))->refresh();
        if($model->name == 'xki') return $this->response()->warning(__('Generator closed.'))->refresh();
     
        $model->status = Channel::STATUS_WAITING;
        $model->save();

        XkvGeneratorJob::dispatch($model->id)->onQueue('xkv');

        \App\Tools\Operation::log($this->name, 'channel/Generator', 'action', $model->toArray());


        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

}