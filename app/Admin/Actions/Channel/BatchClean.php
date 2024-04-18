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
        $list = [];
        foreach ($collection as $model) 
        {
            // if($model->status != Channel::STATUS_READY) {
            //     continue;
            // }

            if($model->lock_status == Channel::LOCK_ENABLE) {
                continue;
            }
            
            ChannelPrograms::where('channel_id', $model->id)->delete();
            $model->status = Channel::STATUS_EMPTY;
            $model->start_end = '';
            $model->save();
            $list[] = $model->toArray();
        }

        \App\Tools\Operation::log('批量清空编单', 'channel/BatchClean', 'action', $list);
        
        return $this->response()->success(__('Clean success message.'))->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定清空节目单吗？ 该操作不可回退！');
    }

}