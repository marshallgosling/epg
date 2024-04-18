<?php

namespace App\Admin\Actions\Material;

use App\Jobs\Material\MediaInfoJob;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchSync extends BatchAction
{
    public $name = '批量同步';
    protected $selector = '.batch-sync';

    public function handle(Collection $collection, Request $request)
    {
        $list = [];
        foreach ($collection as $model) {
            MediaInfoJob::dispatch($model->id, 'sync')->onQueue('media');
            $list[] = [$model->id, $model->path, $model->scan_at];
        }
        \App\Tools\Operation::log($this->name, 'material/BatchSync', 'action', $list);
        return $this->response()->success(__('BatchSync Success message'))->refresh();
    }

    // public function dialog()
    // {
    //     $this->confirm('确认要发起同步？');
    // }

    public function form()
    {
        //$this->checkbox('type', '类型')->options([]);
        $this->textarea('reason', '说明')->value("确认要发起同步请求吗？")->disable();
    }

    public function html()
    {
        return "<a class='batch-sync btn btn-sm btn-info'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }


}