<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\ProgramsJob;
use App\Models\Channel;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchGenerator extends BatchAction
{
    public $name = '生成编单';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) 
        {
            if($model->status != Channel::STATUS_EMPTY) {
                continue;
            }

            ProgramsJob::dispatch($model->uuid);
            $model->status = Channel::STATUS_RUNNING;
            $model->save();
        }
        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

}