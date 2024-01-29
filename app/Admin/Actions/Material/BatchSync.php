<?php

namespace App\Admin\Actions\Material;

use App\Jobs\Material\MediaInfoJob;
use App\Models\Material;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchSync extends BatchAction
{
    public $name = '批量同步';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            MediaInfoJob::dispatch($model->id, 'sync')->onQueue('media');
        }

        return $this->response()->success(__('BatchSync Success message'))->refresh();
    }

}