<?php

namespace App\Admin\Actions\Template;

use App\Jobs\Template\ReverseJob;
use App\Models\Channel;
use Encore\Admin\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Reverse extends Action
{
    public $name = '回退模版及编单';
    protected $selector = '.reverse-action';

    public function handle(Request $request)
    {
        $group = $request->get('channel', 'xkc');
        $action = $request->get('action', 'none');
        if(!Storage::exists($group.'_saved_template')) 
            return $this->response()->error("不能进行回退操作！同一编单只能回退一次。");

        ReverseJob::dispatch($group, $action);

        return $this->response()->success(__('Replicate Success message'));
    }

    public function form()
    {
        $this->select('channel', __('Channel'))->options(['xkc'=>'星空中国', 'xki'=>'星空国际'])->default('xkc')->required();
        $this->radio('action', __('其他'))->options(['none'=>'不删除编单数据', 'clear'=>'同时删除编单数据'])->default('clear')->required();
        $this->textarea("help", "注意说明")->default('回退操作不可撤销'.PHP_EOL.'请再次确认是否要继续该操作！')->disable();
    }

    public function html()
    {
        return "<a class='reverse-action btn btn-sm btn-danger'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }

}