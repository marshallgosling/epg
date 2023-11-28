<?php

namespace App\Admin\Actions\Channel;

use App\Models\Channel;
use App\Models\ChannelPrograms;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchClean extends BatchAction
{
    public $name = '批量清空编单';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) 
        {
            if($model->status != Channel::STATUS_READY) {
                continue;
            }
            
            ChannelPrograms::where('channel_id', $model->id)->delete();
            $model->status = Channel::STATUS_EMPTY;
            $model->save();
        }
        
        return $this->response()->success(__('Clean success message.'))->refresh();
    }

}