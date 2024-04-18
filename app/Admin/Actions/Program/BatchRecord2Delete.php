<?php

namespace App\Admin\Actions\Program;

use App\Models\Record2;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchRecord2Delete extends BatchAction
{
    public $name = '批量删除剧集';

    public function handle(Collection $collection)
    {
        $list = [];
        foreach ($collection as $model) {
            
            if($model->episodes == null) continue;
            if($model->category == 'movie') continue;
            
            if(empty(trim($model->episodes))) continue;

            Record2::where('episodes', $model->episodes)->delete();
            $list[] = $model->episodes;
        }

        \App\Tools\Operation::log($this->name, 'program/BatchRecord2Delete', 'action', $list);

        return $this->response()->success(__('BatchDelete Success message'))->refresh();
    }

    public function dialog()
    {
        $this->confirm("确认要批量删除剧集？");
    }

}