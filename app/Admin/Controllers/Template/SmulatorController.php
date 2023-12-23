<?php

namespace App\Admin\Controllers\Template;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Tools\Simulator\XkcSimulator;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

class SimulatorController extends Controller
{

    public function index(Request $request, Content $content)
    {
        $group = $request->get('group') ?? 'xkc';
        $days = (int)$request->get('days');
        $days = $days == 0 ? 14 : $days;

        $channel = Channel::where(['status'=>Channel::STATUS_EMPTY,'name'=>$group])->orderBy('air_date')->first();
        $begin = $channel ? $channel->air_date : date('Y-m-d');

        $simulator = new XkcSimulator($group);

        $data = $simulator->handle($begin, $days, function ($t) {
            return ' <small class="pull-right text-warning">'.$t['unique_no'].'</small> &nbsp;'.  $t['name'] . ' &nbsp; <small class="text-info">'.substr($t['duration'], 0, 8).'</small>';
        });

        $error = $simulator->getErrorMark();

        return $content->title(__('Simulator Mode'))->description(__('Preview Simulator Content'))
        ->body(view('admin.template.simulator', compact('data', 'group', 'days', 'begin', 'error')));
    }

}
