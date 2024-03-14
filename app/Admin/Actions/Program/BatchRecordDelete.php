<?php

namespace App\Admin\Actions\Program;

use App\Models\Record;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchRecordDelete extends BatchAction
{
    public $name = '批量删除剧集';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            
            if($model->episodes == null) continue;
            if($model->category == 'movie') continue;
            
            if(empty(trim($model->episodes))) continue;

            Record::where('episodes', $model->episodes)->delete();
        }

        return $this->response()->success(__('BatchDelete Success message'))->refresh();
    }

    public function dialog()
    {
        $this->confirm("确认要批量删除剧集？");
    }

}