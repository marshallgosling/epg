<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\ExportJob;
use App\Models\ExportList;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class ToolExporter extends Action
{
    protected $selector = '.export-channels';
    public $name = '批量导出';

    public function handle(Request $request)
    {
        $start_at = $request->get('start_at');
        $end_at = $request->get('end_at') ?? $start_at;
        $name = $request->get('name') ?? "{$start_at}-{$end_at}";
        $s = strtotime($start_at);
        $e = strtotime($end_at);

        if($s > $e) {
            return $this->response()->error('结束日期不能早于开始日期');
        }

        $model = new ExportList();
        $model->start_at = $start_at;
        $model->end_at = $end_at;
        $model->status = ExportList::STATUS_RUNNING;
        $model->name = $name;
        $model->group_id = 'xkv';
        $model->save();

        ExportJob::dispatch($model->id);

        return $this->response()->success('批量生成Excel任务添加成功。')->redirect(admin_url('export/jobs'));
    }

    public function form()
    {
        $this->date('start_at', '开始日期')->required();
        $this->date('end_at', '结束日期');
        $this->text('name', '别名');
    }

    public function html()
    {
        return '<a class="export-channels btn btn-sm btn-danger"><i class="fa fa-upload"></i> 批量导出</a>';
    }

}