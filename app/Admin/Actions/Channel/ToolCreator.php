<?php

namespace App\Admin\Actions\Channel;

use App\Models\Channel;
use App\Jobs\Channel\ProgramsJob;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ToolCreator extends Action
{
    public $name = '批量新增';
    protected $selector = '.create-channel';
    public $group = '';

    public function __construct($group='')
    {
        $this->group = $group;
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $start_at = $request->get('start_at');
        $end_at = $request->get('end_at') ?? $start_at;
        $generate = $request->get('generate') ?? "0";
        $group = $request->get('group');
        $s = strtotime($start_at);
        $e = strtotime($end_at);

        if($s > $e) {
            return $this->response()->error('结束日期不能早于开始日期');
        }

        for($i=0;$i<100;$i++) {
            
            if(Channel::where('air_date', date('Y-m-d', $s))->where('name', $group)->exists())
            {
                $s += 86400;
                continue;
            }

            $ch = new Channel();

            $ch->name = $group;
            $ch->air_date = date('Y-m-d', $s);
            $ch->uuid = (string) Str::uuid();
            $ch->version = 1;
            $ch->status = Channel::STATUS_EMPTY;
            $ch->audit_status = Channel::AUDIT_EMPTY;
            
            if($generate == '1') {
                $ch->status = Channel::STATUS_RUNNING;
                $ch->save();
                ProgramsJob::dispatch($ch->uuid);
            }
            else {
                $ch->save();
            }

            $s += 86400;

            if($s > $e) break;
        }

        
        return $this->response()->success('批量创建节目单成功')->refresh();
    }

    public function form()
    {
        $this->date('start_at', '开始日期')->required();
        $this->date('end_at', '结束日期')->required();
        $this->checkbox('generate', '同时生成节目单')->options([1=>'生成']);
        $this->hidden('group', '分组')->default($this->group);
        $this->textarea('comment', '说明及注意事项')->default('根据日期范围创建节目单。')->disable();
    }

    public function html()
    {
        return "<a class='create-channel btn btn-sm btn-success'><i class='fa fa-plus'></i> {$this->name}</a>";
    }

}