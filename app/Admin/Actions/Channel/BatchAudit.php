<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\AuditEpgJob;
use App\Jobs\EpgJob;
use App\Jobs\StatisticJob;
use App\Models\Channel;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;

class BatchAudit extends BatchAction
{
    public $name = '批量审核';
    protected $selector = '.audit-channel';

    public function handle(Collection $collection, Request $request)
    {
        $list = [];
        foreach ($collection as $model) 
        { 
            if(in_array($model->status, [Channel::STATUS_READY, Channel::STATUS_DISTRIBUTE])) {
                AuditEpgJob::dispatch($model->id, Admin::user()->name)->onQueue('audit');
                $list[] = $model->toArray();
            }
        }
        \App\Tools\Operation::log($this->name, 'channel/BatchAudit', 'action', $list);
        return $this->response()->success('批量审核任务已提交。')->refresh();
    }

    public function form()
    {
        //$this->select('lock', '状态')->options(Channel::LOCKS)->rules('required');
        //$this->textarea('comment', '意见');
        $this->text("help", "注意说明")->default('空编单和停止使用的编单不能进行审核')->disable();
    }

    public function html()
    {
        return "<a class='audit-channel btn btn-sm btn-warning'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }

}