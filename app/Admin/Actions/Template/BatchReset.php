<?php

namespace App\Admin\Actions\Template;

use App\Models\TemplateRecords;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchReset extends BatchAction
{
    public $name = '批量重置';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            $data = $model->data;
            if(key_exists('unique_no', $data)) $data['unique_no'] = '';
            if(key_exists('result', $data)) $data['result'] = '';
            if(key_exists('name', $data)) $data['name'] = '';

            if($model->type == TemplateRecords::TYPE_RANDOM) $data['episodes'] = '';

            $model->data = $data;
            if($model->isDirty()) $model->save();
        }

        return $this->response()->success(__('BatchReset Success message'))->refresh();
    }

    public function dialog()
    {
        $this->confirm(__('Confirm all reset ?'));
    }

}