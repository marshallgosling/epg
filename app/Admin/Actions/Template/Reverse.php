<?php

namespace App\Admin\Actions\Template;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class Reverse extends RowAction
{
    public $name = '回退';

    public function handle(Model $model)
    {
        // $model ...
        $model->replicate()->save();

        return $this->response()->success(__('Replicate Success message'))->refresh();
    }

}