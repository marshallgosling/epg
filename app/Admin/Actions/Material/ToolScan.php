<?php

namespace App\Admin\Actions\Material;

use App\Models\Folder;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use App\Jobs\Material\ScanFolderJob;

class ToolScan extends Action
{
    public $name = '扫描播出池目录';
    protected $selector = '.tool-scan';
    public $folder = 0;

    public function __construct($folder_id='0')
    {
        $this->folder = (int)$folder_id;
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $folder = $request->get('folder');
        $model = Folder::find($folder);
        if(!$model || $model->status == Folder::STATUS_SCAN) {
            return $this->response()->error(__('任务已经在扫描队列中'))->refresh();
        };
        $model->status = Folder::STATUS_SCAN;
        $model->save();
        ScanFolderJob::dispatch($model->id, 'scan')->onQueue('media');
        return $this->response()->success(__('扫描任务已启动'))->refresh();
    }

    public function form()
    {
        $this->hidden('folder')->default($this->folder);
        $this->textarea("help", "注意说明")->default('提交后发起扫描任务系统自动进行识别。'.PHP_EOL.'此过程需要花费十几秒。')->disable();
    }

    public function html()
    {
        return "<a class='tool-scan btn btn-sm btn-warning'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }
}