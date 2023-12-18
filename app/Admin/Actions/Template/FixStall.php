<?php

namespace App\Admin\Actions\Template;

use App\Tools\Notify;
use App\Models\Notification;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FixStall extends Action
{
    public $name = '处理问题';
    protected $selector = '.fix-stall';

    public $group = 'xkc';

    public function handle(Request $request)
    {
        if(!Storage::disk('data')->exists('generate_stall'))
            return $this->response()->success('无需处理');
        $desc = $request->get('desc') ?? "";
        $fixed = $request->get('fixed') ?? 0;

        if($fixed) {
            Storage::disk('data')->delete('generate_stall');
            Notify::fireNotify(
                $this->group,
                Notification::TYPE_GENERATE, 
                "节目单自动生成模版错误已解决", 
                "处理日期时间: ".date('Y-m-d H:i:s').' 描述: '.$desc,
                Notification::LEVEL_INFO
            );
            return $this->response()->success('提交成功')->refresh();
        }

        return $this->response()->warning('问题未解决');
        
    }

    public function form()
    {
        $this->checkbox('fixed', __('问题'))->options([1=>'已解决']);
        $error = '无';
        if(Storage::disk('data')->exists('generate_stall')) $error = Storage::disk('data')->get('generate_stall');
        $this->textarea('desc', __('问题说明'))->default($error);
    
        $this->text('comment', __('说明及注意事项'))->default(__('解决问题后，才能继续自动生成编单。'))->disable();
    }

    public function html()
    {
        return '<a class="fix-stall btn btn-sm btn-danger"><i class="fa fa-upload"></i> '.__($this->name).'</a>';
    }
}