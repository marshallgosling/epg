<?php

namespace App\Admin\Actions\Material;

use App\Models\Material;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchCheckMediaFile extends BatchAction
{
    public $name = '批量启用';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            
        }

        return $this->response()->success(__('BatchEnabled Success message'))->refresh();
    }

}