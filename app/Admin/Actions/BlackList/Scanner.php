<?php

namespace App\Admin\Actions\BlackList;

use App\Jobs\BlackListJob;
use App\Models\BlackList;
use Encore\Admin\Actions\RowAction;

class Scanner extends RowAction
{
    public $name = '扫描编单';

    /**
     * Handle action
     * 
     * @param BlackList $model
     */
    public function handle(BlackList $model)
    {
        // $model ...
        if($model->status == BlackList::STATUS_RUNNING) {
            return $this->response()->error(__('Scanner start failed message.'))->refresh();
        }

        BlackListJob::dispatch($model->id);
        $model->status = BlackList::STATUS_RUNNING;
        $model->save();

        return $this->response()->success(__('Scanner start success message.'))->refresh();
    }

}