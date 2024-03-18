<?php

namespace App\Admin\Actions\Material;

use App\Jobs\Material\MediaInfoJob;
use App\Models\LargeFile;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchProcessLargeFile extends BatchAction
{
    public $name = '批量处理文件';
    protected $selector = '.batch-largefiles';

    public function handle(Collection $collection, Request $request)
    {
        foreach ($collection as $model) {
            if($model->status == LargeFile::STATUS_EMPTY)
                MediaInfoJob::dispatch($model->id, 'process')->onQueue('media');
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
        $this->textarea('reason', '说明')->value("确认要发起处理请求吗？")->disable();
    }

    public function html()
    {
        return "<a class='batch-largefiles btn btn-sm btn-info'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }


}