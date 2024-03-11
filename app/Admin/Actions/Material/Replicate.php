<?php

namespace App\Admin\Actions\Material;

use App\Events\Channel\CalculationEvent;
use App\Models\Material;
use Encore\Admin\Actions\RowAction;
use Illuminate\Support\Str;

class Replicate extends RowAction
{
    public $name = '复制';

    public function handle(Material $model)
    {
        $m = Material::find($model->id)->replicate();
        $m->unique_no = 'TEMP'.Str::random(8);
        $m->status = Material::STATUS_EMPTY;
        $m->filepath = '';

        $m->save();

        return $this->response()->success(__('Replicate Success message'))->refresh();
    }

}