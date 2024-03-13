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
        
        foreach ($collection as $model) 
        {
            
            if($model->status == Channel::STATUS_READY) {
                AuditEpgJob::dispatch($model->id);
            }
        }
        
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
        return "<a class='audit-channel btn btn-sm btn-primary'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }

}