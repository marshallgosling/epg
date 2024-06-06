<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\EpgJob;
use App\Jobs\StatisticJob;
use App\Models\Channel;
use Encore\Admin\Actions\RowAction;

class Lock extends RowAction
{
    public $name = '加/解锁';

    public function handle(Channel $model)
    {
        // $model ...
        if(!in_array($model->status, [Channel::STATUS_READY, Channel::STATUS_DISTRIBUTE])) {
            return $this->response()->error(__('频道编单状态异常'))->refresh();
        }
        
        $lock = $model->lock_status;

        $model->lock_status = $lock == Channel::LOCK_EMPTY ? Channel::LOCK_ENABLE : Channel::LOCK_EMPTY;
        if($model->comment == '编单已完成，请加锁并审核！') $model->comment = '';
        
        $model->save();
            
        if($model->lock_status == Channel::LOCK_ENABLE) {
            StatisticJob::dispatch($model->id);
            EpgJob::dispatch($model->id);
        }

        $lock = $model->lock_status;
        \App\Tools\Operation::log($this->name, 'channel/BatchLock:'.$lock, 'action', $model);

        return $this->response()->success($lock?"加锁成功":"解锁成功")->refresh();
    }

}