<?php

namespace App\Admin\Actions\ChannelProgram;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use App\Events\Channel\CalculationEvent;

class BatchReplicate extends BatchAction
{
    public $name = '批量复制';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            $model->replicate()->save();
        }

        CalculationEvent::dispatch($model->channel_id);

        return $this->response()->success(__('BatchReplicate Success message'))->refresh();
    }

}