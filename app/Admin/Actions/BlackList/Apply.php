<?php

namespace App\Admin\Actions\BlackList;

use App\Jobs\BlackListJob;
use App\Models\BlackList;
use Encore\Admin\Actions\RowAction;

class Apply extends RowAction
{
    public $name = '处理扫描';

    /**
     * Handle action
     * 
     * @param BlackList $model
     */
    public function handle(BlackList $model)
    {
        // $model ...
        if($model->status == BlackList::STATUS_RUNNING) {
            return $this->response()->error('正在运行中，请稍后')->refresh();
        }

        BlackListJob::dispatch($model->id, 'apply');
        $model->status = BlackList::STATUS_RUNNING;
        $model->save();

        return $this->response()->success('任务启动成功')->refresh();
    }

}