<?php

namespace App\Admin\Actions\ChannelProgram;

use App\Events\Channel\CalculationEvent;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class Replicate extends RowAction
{
    public $name = '复制';

    public function handle(ChannelPrograms $model)
    {
        $channel = Channel::find($model->channel_id);
        if($channel->audit_status == Channel::AUDIT_PASS) {
            return $this->response()->success(__('Replicate Failed message'))->refresh();
        }

        $model->replicate()->save();

        CalculationEvent::dispatch($model->channel_id);

        return $this->response()->success(__('Replicate Success message'))->refresh();
    }

}