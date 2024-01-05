<?php

namespace App\Admin\Actions\Channel;

use App\Jobs\Channel\XkvGeneratorJob;
use App\Jobs\Channel\XkcGeneratorJob;
use App\Jobs\Channel\XkiGeneratorJob;
use App\Models\Channel;
use App\Tools\Generator\XkcGenerator;
use App\Tools\Generator\XkiGenerator;
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

        if($group == 'xkc' && Storage::disk('data')->exists(XkcGenerator::STALL_FILE))
        {
            return $this->response()->error('您有未处理的模版编排错误，请先进入模版页面，解决模版问题，然后点击“模拟编单测试”按钮。');
        }

        if($group == 'xki' && Storage::disk('data')->exists(XkiGenerator::STALL_FILE))
        {
            return $this->response()->error('您有未处理的模版编排错误，请先进入模版页面，解决模版问题，然后点击“模拟编单测试”按钮。');
        }

        if(Channel::where(['status'=>Channel::STATUS_ERROR,'name'=>$group])->exists())
        {
            return $this->response()->error('节目单有状态为“错误”的情况，请先处理错误的节目单后才能继续。');
        }

        $last = Channel::where(['status'=>Channel::STATUS_EMPTY,'name'=>$group])->orderBy('air_date', 'desc')->first();
        if(!$last) {
            return $this->response()->error('没有节目单需要生成');
        }

        $start_at = $request->get('generate_start_at');
        $end_at = $request->get('generate_end_at') ?? $last->air_date;
        
        $s = strtotime($start_at);
        $e = strtotime($end_at);

        $max = $s + 86400 * (int)config('SIMULATOR_DAYS', 14) - 86400;

        if($s > $e) {
            return $this->response()->error('结束日期不能早于开始日期');
        }

        if($e > $max) $end_at = date('Y-m-d', $max);

        $channels = Channel::where(['status'=>Channel::STATUS_EMPTY,'name'=>$group])
                    ->where('air_date','>=',$start_at)->where('air_date','<=',$end_at)->get();
        
        if($channels) {
            foreach($channels as $model) {
                if($model->status == Channel::STATUS_EMPTY) {
                    $model->status = Channel::STATUS_WAITING;
                    $model->save();
                }
            }
        
            if($group == 'xkc')
                XkcGeneratorJob::dispatch($group)->onQueue('xkc');
            else if($group == 'xki')
                XkiGeneratorJob::dispatch($group)->onQueue('xki');
            else
                XkvGeneratorJob::dispatch($group)->onQueue('xkv');
        }

        return $this->response()->success(__('Generator start success message.'))->refresh();
    }

    public function form()
    {
        $channel = Channel::where(['status'=>Channel::STATUS_EMPTY,'name'=>$this->group])->orderBy('air_date')->first();
        $c = $channel ? $channel->air_date : date('Y-m-d');
        
        $this->text('info', '开始日期')->default($c)->disable();
        $this->date('generate_end_at', '结束日期')->placeholder('不填则自动结束');
        $this->hidden('generate_start_at', '开始日期')->default($c);
        $this->hidden('generate_group', '分组')->default($this->group);
        $this->textarea('comment', '说明及注意事项')->default("串联单固定按日期生成，从近到远的顺序。\n如果节目单有状态为“错误”的情况，则自动生成不会进行\n最多生成 ".config('SIMULATOR_DAYS', 14). ' 天')->disable();
        
    }

    public function html()
    {
        return '<a class="generate-epg btn btn-sm btn-info"><i class="fa fa-android"></i> '.$this->name.'</a>';
    }

}