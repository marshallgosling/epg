<?php

namespace App\Admin\Actions\ChannelProgram;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use App\Events\Channel\CalculationEvent;
use App\Models\Channel;

class BatchReplicate extends BatchAction
{
    public $name = '批量复制';

    public function handle(Collection $collection)
    {
        
        $channel = null;
        foreach ($collection as $model) {
            if($channel == null) {
                $channel = Channel::find($model->channel_id);
                if($channel->lock_status == Channel::LOCK_ENABLE) {
                    return $this->response()->success(__('BatchReplicate Failed message'))->refresh();
                }
            }
            $model->replicate()->save();
        }

        CalculationEvent::dispatch($model->channel_id);

        \App\Tools\Operation::log('批量复制节目记录', 'ChannelProgram/BatchReplicate', 'action', []);

        return $this->response()->success(__('BatchReplicate Success message'))->refresh();
    }

}