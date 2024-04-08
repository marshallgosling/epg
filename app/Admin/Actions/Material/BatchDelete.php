<?php

namespace App\Admin\Actions\Material;

use App\Models\Material;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchDelete extends BatchAction
{
    public $name = '批量删除剧集';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            
            if($model->group == null) continue;
            if($model->category == 'movie') continue;
            if($model->channel == 'xkv') continue;
            if(empty(trim($model->group))) continue;

            Material::where('group', $model->group)->delete();
        }

        return $this->response()->success(__('BatchDelete Success message'))->refresh();
    }

    public function dialog()
    {
        $this->confirm("确认要批量删除剧集？");
    }

}