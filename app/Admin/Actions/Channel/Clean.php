<?php

namespace App\Admin\Actions\Channel;

use App\Models\Channel;
use App\Models\ChannelPrograms;
use Encore\Admin\Actions\RowAction;

class Clean extends RowAction
{
    public $name = '清空编单';

    public function handle(Channel $model)
    {
        // if($model->status != Channel::STATUS_READY) {
        //     return $this->response()->error(__('Clean failed message.'))->refresh();
        // }

        if($model->lock_status == Channel::LOCK_ENABLE) {
            return $this->response()->error(__('Clean failed message.'))->refresh();
        }
            
        ChannelPrograms::where('channel_id', $model->id)->delete();
        $model->status = Channel::STATUS_EMPTY;
        $model->start_end = '';
        $model->save();
        
        
        return $this->response()->success(__('Clean success message.'))->refresh();
    }

}