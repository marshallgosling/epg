<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\DistributionJob;
use App\Jobs\Material\MediaInfoJob;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchDistributor extends BatchAction
{
    public $name = '批量分发编单';
    protected $selector = '.batch-distributor';

    public function handle(Collection $collection, Request $request)
    {
        foreach ($collection as $model) {
            MediaInfoJob::dispatch($model->id, 'distribute')->onQueue('media');
        }

        return $this->response()->success(__('BatchSync Success message'))->refresh();
    }

    // public function dialog()
    // {
    //     $this->confirm('确认要发起同步？');
    // }

    public function form()
    {
        //$this->checkbox('type', '类型')->options([]);
        $this->textarea('reason', '说明')->value("确认要发起分发编单请求吗？")->disable();
    }

    public function html()
    {
        return "<a class='batch-distributor btn btn-sm btn-success'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }


}