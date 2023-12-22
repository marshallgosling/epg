<?php

namespace App\Admin\Actions\ChannelProgram;

use App\Events\Channel\CalculationEvent;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ToolCalculate extends Action
{
    protected $selector = '.calculation';
    public $name = '确认要重新计算节目单时间？';
    public $channel_id;

    public function __construct($channel_id='')
    {
        $this->channel_id = $channel_id;
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $id = $request->get('channel');

        if($id) {
            
            CalculationEvent::dispatch($id[0]);
            return $this->response()->success('计算时间完成。')->refresh();
        }
        
    }

    public function form()
    {
        $type = [
            $this->channel_id => DB::table('channel')->where('id', $this->channel_id)->value('air_date')
        ];
        $this->checkbox('channel', '节目单日期')->default($this->channel_id)->options($type);
        $this->text('begin_at', __('Start at'))->default(config('EPG_BEGIN_TIME', '17:00:00'));
    }

    public function html()
    {
        return '<a class="calculation btn btn-sm btn-danger"><i class="fa fa-calculator"></i> 重新计算时间</a>';
    }

}