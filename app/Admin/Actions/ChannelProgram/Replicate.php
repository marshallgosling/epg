<?php

namespace App\Admin\Actions\ChannelProgram;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class Replicate extends RowAction
{
    public $name = '复制';

    public function handle(Model $model)
    {
        // $model ...
        $model->replicate()->save();

        return $this->response()->success(__('Replicate Success message'))->refresh();
    }

}