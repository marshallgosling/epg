<?php

namespace App\Admin\Actions\Notification;

use App\Tools\Notify;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchViewed extends BatchAction
{
    public $name = '批量已读';

    public function handle(Collection $collection)
    {
        
        foreach ($collection as $model) 
        {
            $model->viewed = 1;
            $model->save();
        }

        Notify::writeAllNotificationToRedis();
        
        return $this->response()->success(__('Batch viewed success message.'))->refresh();
    }

}