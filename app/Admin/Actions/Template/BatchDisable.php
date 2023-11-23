<?php

namespace App\Admin\Actions\Template;

use App\Models\Template;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchDisable extends BatchAction
{
    public $name = '批量停用';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            $model->status = Template::STATUS_STOPED;
            $model->save();
        }

        return $this->response()->success(__('BatchDisabled Success message'))->refresh();
    }

}