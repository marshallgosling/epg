<?php

namespace App\Admin\Actions\Channel;

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
        $audit = (int)$request->get('audit');
        $comment = $request->get('comment');
        foreach ($collection as $model) 
        {
            
            if($audit == Channel::AUDIT_PASS && $model->status != Channel::STATUS_READY) {
                // 空编单和停止使用的编单不能通过审核
                continue;
            }
            $model->audit_status = $audit;
            $model->comment = $comment;
            $model->reviewer = Admin::user()->name;
            $model->audit_date = now();
            $model->save();
            // Channel::where('id', $model->id)->update(['audit_status', $request->get('audit'), 'comment'=>$request->get('comment')]);

        }
        
        return $this->response()->success(__('Clean success message.'))->refresh();
    }

    public function form()
    {
        $this->radio('audit', '状态')->options(Channel::AUDIT)->rules('required');
        $this->textarea('comment', '审核意见')->rules('required');
        $this->text("help", "注意说明")->default('空编单和停止使用的编单不能通过审核')->disable();
    }

    public function html()
    {
        return "<a class='audit-channel btn btn-sm btn-warning'><i class='fa fa-info-circle'></i>批量审核</a>";
    }

}