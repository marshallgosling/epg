<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\ExcelJob;
use App\Models\ExportList;
use App\Models\TemplatePrograms;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class ToolExporter extends Action
{
    protected $selector = '.export-channels';
    public $name = '批量导出';
    public $group = '';
    public $type = 0;

    public function __construct($group='', $type=0)
    {
        $this->group = $group;
        $this->type = $type;
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $start_at = $request->get('export_start_at');
        $end_at = $request->get('export_end_at', $start_at);
        $name = $request->get('export_name', "{$start_at}-{$end_at}");
        $group = $request->get('export_group', 'xkc');
        $type = $request->get('export_type', 0);
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
        $model->type = $type;
        $model->group_id = $group;
        $model->save();

        ExcelJob::dispatch($model->id);

        return $this->response()->success('批量生成Excel任务添加成功。')->redirect(admin_url('export/excel'));
    }

    public function form()
    {
        $this->date('export_start_at', '开始日期')->required();
        $this->date('export_end_at', '结束日期');
        $this->text('export_name', '别名');
        $this->radio('export_type', '类型')->options(ExportList::TYPES)->default($this->type);
        $this->hidden('export_group', '分组')->default($this->group);
        $this->textarea('comment', '说明及注意事项')->default('只会导出的状态为正常的节目单。')->disable();
    }

    public function html()
    {
        return '<a class="export-channels btn btn-sm btn-danger"><i class="fa fa-upload"></i> 批量导出</a>';
    }

}