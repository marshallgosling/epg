<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\EpgJob;
use App\Jobs\StatisticJob;
use App\Models\Channel;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;

class BatchLock extends BatchAction
{
    public $name = '批量锁定';
    protected $selector = '.lock-channel';

    public function handle(Collection $collection, Request $request)
    {
        $lock = (int)$request->get('lock');
        //$comment = $request->get('comment');
        foreach ($collection as $model) 
        {
            if($lock == Channel::LOCK_ENABLE && $model->status != Channel::STATUS_READY) {
                // 空编单和停止使用的编单不能通过锁定
                continue;
            }
            $model->audit_status = $lock;
            //$model->comment = $comment;
            $model->reviewer = Admin::user()->name;
            //$model->audit_date = now();
            $model->save();
            // Channel::where('id', $model->id)->update(['audit_status', $request->get('audit'), 'comment'=>$request->get('comment')]);

            if($lock == Channel::LOCK_ENABLE) {
                StatisticJob::dispatch($model->id);
                EpgJob::dispatch($model->id);
            }
        }
        
        return $this->response()->success(__('Clean success message.'))->refresh();
    }

    public function form()
    {
        $this->select('lock', '状态')->options(Channel::LOCKS)->rules('required');
        //$this->textarea('comment', '意见');
        $this->text("help", "注意说明")->default('空编单和停止使用的编单不能锁定')->disable();
    }

    public function html()
    {
        return "<a class='lock-channel btn btn-sm btn-warning'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }

}