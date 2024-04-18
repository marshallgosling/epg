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
        if($channel->lock_status == Channel::LOCK_ENABLE) {
            return $this->response()->success(__('Replicate Failed message'))->refresh();
        }

        $new = $model->replicate();
        $new->save();

        CalculationEvent::dispatch($model->channel_id);
        
        \App\Tools\Operation::log('复制节目记录', 'ChannelProgram/Replicate', 'action', ['new'=>$new->toArray()]);

        return $this->response()->success(__('Replicate Success message'))->refresh();
    }

}