<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\EpgJob;
use App\Jobs\StatisticJob;
use App\Models\Channel;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchLock extends BatchAction
{
    public $name = '批量调整状态锁';
    protected $selector = '.lock-channel';

    public function handle(Collection $collection, Request $request)
    {
        $lock = (int)$request->get('lock');
        //$comment = $request->get('comment');
        foreach ($collection as $model) 
        {
            if(in_array($model->status, [Channel::STATUS_ERROR, Channel::STATUS_CLOSE, Channel::STATUS_EMPTY, Channel::STATUS_RUNNING])) {
                // 空编单和停止使用的编单不能通过锁定
                continue;
            }
            $model->lock_status = $lock;
            if($model->comment == '编单已完成，请加锁并审核！') $model->comment = '';

            $model->save();
            
            if($lock == Channel::LOCK_ENABLE) {
                StatisticJob::dispatch($model->id);
                EpgJob::dispatch($model->id);
            }

            $list[] = $model->toArray();
        }
        \App\Tools\Operation::log($this->name, 'channel/BatchLock:'.$lock, 'action', $list);
        return $this->response()->success($lock?"加锁成功":"解锁成功")->refresh();
    }

    public function form()
    {
        $this->select('lock', '状态')->options(Channel::LOCKS)->rules('required');
        //$this->textarea('comment', '意见');
        $this->text("help", "注意说明")->default('空编单和停止使用的编单不能锁定')->disable();
    }

    public function html()
    {
        return "<a class='lock-channel btn btn-sm btn-primary'><i class='fa fa-lock'></i> {$this->name}</a>";
    }

}