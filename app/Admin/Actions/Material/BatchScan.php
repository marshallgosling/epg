<?php

namespace App\Admin\Actions\Material;

use App\Jobs\Material\ScanFolderJob;
use App\Models\Folder;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchScan extends BatchAction
{
    public $name = '批量扫描';
    protected $selector = '.batch-scan';

    public function handle(Collection $collection, Request $request)
    {
        $list = [];
        foreach ($collection as $model) {
            if($model->status == Folder::STATUS_SCAN) continue;
            $model->status = Folder::STATUS_SCAN;
            $model->save();
            ScanFolderJob::dispatch($model->id, 'scan')->onQueue('media');
            $list[] = [$model->id, $model->path, $model->scan_at];
        }
        \App\Tools\Operation::log($this->name, 'material/BatchScan', 'action', $list);
        return $this->response()->success(__('BatchScan Success message'))->refresh();
    }

    // public function dialog()
    // {
    //     $this->confirm('确认要发起同步？');
    // }

    public function form()
    {
        //$this->checkbox('type', '类型')->options([]);
        $this->textarea('reason', '说明')->value("确认要发起扫描请求吗？")->disable();
    }

    public function html()
    {
        return "<a class='batch-scan btn btn-sm btn-primary'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }


}