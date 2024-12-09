<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\XkvGeneratorJob;
use App\Jobs\Channel\XkcGeneratorJob;
use App\Jobs\Channel\XkiGeneratorJob;
use App\Models\Channel;
use App\Tools\Generator\XkcGenerator;
use App\Tools\Generator\XkiGenerator;
use App\Tools\Operation;
use Illuminate\Support\Facades\Storage;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class ToolGenerator extends Action
{
    protected $selector = '.generate-epg';
    public $name = '自动生成串联单';
    public $group = '';

    public function __construct($group='')
    {
        $this->group = $group;
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $group = $request->get('generate_group');

        
        if(Channel::where(['status'=>Channel::STATUS_ERROR,'name'=>$group])->exists())
        {
            return $this->response()->error('节目单有状态为“错误”的情况，请先处理错误的节目单后才能继续。');
        }
        
        $start_at = $request->get('generate_start_at');
            
        $s = strtotime($start_at);
        $max = $s + 86400 * (int)config('SIMULATOR_DAYS', 14) - 86400;

        $end_at = $request->get('generate_end_at') ?? false;
        if($end_at) $e = strtotime($end_at);
        else $e = $max;

        if($s > $e) {
            return $this->response()->error('结束日期不能早于开始日期');
        }

        if($e > $max) $end_at = date('Y-m-d', $max);

        if($group == 'xkc' && Storage::disk('data')->exists(XkcGenerator::STALL_FILE))
        {
            $date = Storage::disk('data')->get(XkcGenerator::STALL_FILE);
            $ts = strtotime($date);
            if($e>=$ts)
                return $this->response()->error('您有未处理的模版编排错误，请先进入模版页面，解决模版问题，然后点击“模拟编单测试”按钮。');
        }

        if($group == 'xki' && Storage::disk('data')->exists(XkiGenerator::STALL_FILE))
        {
            $date = Storage::disk('data')->get(XkiGenerator::STALL_FILE);
            $ts = strtotime($date);
            if($e>=$ts)
                return $this->response()->error('您有未处理的模版编排错误，请先进入模版页面，解决模版问题，然后点击“模拟编单测试”按钮。');
        }

        if($group == 'xkc')
            XkcGeneratorJob::dispatch(compact('s','e'))->onQueue('xkc');
        else if($group == 'xki')
            XkiGeneratorJob::dispatch(compact('s','e'))->onQueue('xki');
        else
            XkvGeneratorJob::dispatch(compact('s','e'))->onQueue('xkv');
        
        Operation::log('自动生成节目编单', 'channel/ToolGenerator', 'action', compact('group', 'start_at', 'end_at'));
        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

    public function form()
    {
        $channel = Channel::where(['status'=>Channel::STATUS_READY,'name'=>$this->group])->orderBy('air_date','desc')->first();
        if(!$channel) {
            $channel = Channel::where(['status'=>Channel::STATUS_DISTRIBUTE,'name'=>$this->group])->orderBy('air_date','desc')->first();
        }
        $c = strtotime($channel->air_date) + 86400;
        $this->text('info', '开始日期')->default(date('Y-m-d', $c))->disable();
        $this->hidden('generate_start_at', '开始日期')->default(date('Y-m-d', $c));
        
        $this->date('generate_end_at', '结束日期')->placeholder('不填则自动结束');
        $this->hidden('generate_group', '分组')->default($this->group);
        $this->textarea('comment', '说明及注意事项')->default("串联单固定按日期生成，从近到远的顺序。\n如果节目单有状态为“错误”的情况，则自动生成不会进行\n最多生成 ".config('SIMULATOR_DAYS', 14). ' 天')->disable();
        
    }

    public function html()
    {
        return '<a class="generate-epg btn btn-sm btn-info"><i class="fa fa-android"></i> '.$this->name.'</a>';
    }

}
