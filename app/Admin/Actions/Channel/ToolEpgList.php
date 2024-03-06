<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\ExportJob;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;
use Encore\Admin\Actions\BatchAction;

class ToolEpgList extends BatchAction
{
    public $name = '重新生成';
    protected $selector = '.xml-channel';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) 
        {
            if($model->audit_status != Channel::AUDIT_PASS || $model->status != Channel::STATUS_READY) {
                // 空编单和停止使用的编单不能通过审核
                continue;
            }
            //if($model->name == 'xkc')
                ExportJob::dispatch($model->id);
        }
        
        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定重新生成串联单吗？'.PHP_EOL.'该操作不可回退！');
    }

    public function html()
    {
        return "<a class='xml-channel btn btn-sm btn-warning'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }
}