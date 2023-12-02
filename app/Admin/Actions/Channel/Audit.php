<?php

namespace App\Admin\Actions\Channel;

use App\Models\Channel;
use App\Models\ChannelPrograms;
use Encore\Admin\Actions\RowAction;

class Audit extends RowAction
{
    public $name = '审核';

    public function handle(Channel $model)
    {
        if($model->status != Channel::STATUS_READY) {
            $this->response()->error(__('Clean failed message.'))->refresh();
        }
            
        ChannelPrograms::where('channel_id', $model->id)->delete();
        $model->status = Channel::STATUS_EMPTY;
        $model->save();
        
        
        return $this->response()->success(__('Clean success message.'))->refresh();
    }

}