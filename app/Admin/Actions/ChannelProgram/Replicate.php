<?php

namespace App\Admin\Actions\ChannelProgram;

use App\Events\Channel\CalculationEvent;
use App\Models\ChannelPrograms;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class Replicate extends RowAction
{
    public $name = '复制';

    public function handle(ChannelPrograms $model)
    {
        // $model ...
        $model->replicate()->save();

        CalculationEvent::dispatch($model->channel_id);

        return $this->response()->success(__('Replicate Success message'))->refresh();
    }

}