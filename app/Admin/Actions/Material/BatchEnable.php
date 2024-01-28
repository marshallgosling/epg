<?php

namespace App\Admin\Actions\Template;

use App\Models\Template;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchEnable extends BatchAction
{
    public $name = '批量启用';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            $model->status = Template::STATUS_SYNCING;
            $model->save();
        }

        return $this->response()->success(__('BatchEnabled Success message'))->refresh();
    }

}