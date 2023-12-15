<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\ProgramsJob;
use App\Jobs\Channel\RecordJob;
use App\Models\Channel;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchGenerator extends BatchAction
{
    public $name = '批量生成编单';

    public function handle(Collection $collection)
    {
        //return $this->response()->success(__('Generator closed.'))->refresh();

        $group = '';
        foreach ($collection as $model) 
        {
            if($model->status != Channel::STATUS_EMPTY) {
                continue;
            }

            if($model->audit_status == Channel::AUDIT_PASS) {
                continue;
            }
            
            $model->status = Channel::STATUS_RUNNING;
            $model->save();

            $group = $model->name;
        }

        RecordJob::dispatch($group);
        
        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

}