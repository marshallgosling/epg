<?php

namespace App\Admin\Controllers\Template;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Tools\Simulator\XkcSimulator;
use App\Tools\Simulator\XkiSimulator;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

class SimulatorController extends Controller
{

    public function xkc(Request $request, Content $content)
    {
        $group = $request->get('group') ?? 'xkc';
        $days = (int)$request->get('days');
        $days = $days == 0 ? (int)config('SIMULATOR_DAYS', 14) : $days;

        $channel = Channel::where(['status'=>Channel::STATUS_EMPTY,'name'=>$group])->orderBy('air_date')->first();
        $begin = $channel ? $channel->air_date : '2024-01-01';
        $channels = XkcSimulator::generateFakeChannels($begin, $days);
        $simulator = new XkcSimulator($group, $days, $channels);

        $data = $simulator->handle(function ($t) {
            return ' <small class="pull-right text-warning">'.$t['unique_no'].'</small> &nbsp;'.  $t['name'] . ' &nbsp; <small class="text-info">'.substr($t['duration'], 0, 8).'</small>';
        });

        $error = $simulator->getErrorMark();

        return $content->title(__('Simulator Mode'))->description(__('Preview Simulator Content'))
        ->body(view('admin.template.simulator', compact('data', 'group', 'days', 'begin', 'error')));
    }

    public function xki(Request $request, Content $content)
    {
        $group = $request->get('group') ?? 'xki';
        $days = (int)$request->get('days');
        $days = $days == 0 ? (int)config('SIMULATOR_DAYS', 14) : $days;

        $channel = Channel::where(['status'=>Channel::STATUS_EMPTY,'name'=>$group])->orderBy('air_date')->first();
        $begin = $channel ? $channel->air_date : '2024-01-01';
        $channels = XkiSimulator::generateFakeChannels($begin, $days, 'xki');
        $simulator = new XkiSimulator($group, $days, $channels);

        $data = $simulator->handle(function ($t) {
            return ' <small class="pull-right text-warning">'.$t['unique_no'].'</small> &nbsp;'.  $t['name'] . ' &nbsp; <small class="text-info">'.substr($t['duration'], 0, 8).'</small>';
        });

        $error = $simulator->getErrorMark();

        return $content->title(__('Simulator Mode'))->description(__('Preview Simulator Content'))
        ->body(view('admin.template.simulator', compact('data', 'group', 'days', 'begin', 'error')));
    }

}
