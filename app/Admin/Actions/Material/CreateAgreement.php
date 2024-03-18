<?php

namespace App\Admin\Actions\Material;

use App\Models\Agreement;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class CreateAgreement extends Action
{
    public $name = '创建合同';
    protected $selector = '.agreement-create';

    public function handle(Request $request)
    {
        $name = $request->get('name');
        $start_at = $request->get('start_at');
        $end_at = $request->get('end_at');
        $comment = $request->get('comment', '');
        $status = Agreement::STATUS_READY;

        $m = Agreement::create(compact('name', 'start_at', 'end_at', 'comment', 'status'));
        if($m)
            return $this->response()->success("创建合同成功");
        else
            return $this->response()->error("创建合同失败");
    }

    public function form()
    {
        $this->text('name', __('Name'))->required();
        $this->date('start_at', __('Start at'))->default(date('Y-m-d'))->required();;
        $this->date('end_at', __('End at'))->default(date('Y-m-d'))->required();;
        $this->text('comment', __('Comment'));
        $this->textarea('reason', '说明')->value("创建一个合同")->disable();
    }

    public function html()
    {
        return "<a class='agreement-create btn btn-sm btn-primary'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }


}