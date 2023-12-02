<?php

namespace App\Admin\Actions\Channel;

use App\Models\Channel;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchAudit extends BatchAction
{
    public $name = '批量审核';
    protected $selector = '.audit-channel';

    public function handle(Collection $collection, Request $request)
    {
        foreach ($collection as $model) 
        {
            $model->audit_status = $request->get('audit');
            $model->comment = $request->get('comment');
            $model->save();
            // Channel::where('id', $model->id)->update(['audit_status', $request->get('audit'), 'comment'=>$request->get('comment')]);

        }
        
        return $this->response()->success(__('Clean success message.'))->refresh();
    }

    public function form()
    {
        $this->radio('audit', '状态')->options(Channel::AUDIT)->rules('required');
        $this->textarea('comment', '审核意见')->rules('required');
    }

    public function html()
    {
        return "<a class='audit-channel btn btn-sm btn-warning'><i class='fa fa-info-circle'></i>批量审核</a>";
    }

}