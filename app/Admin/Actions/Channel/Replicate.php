<?php

namespace App\Admin\Actions\Channel;

use App\Events\Channel\CalculationEvent;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Tools\ChannelGenerator;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Replicate extends RowAction
{
    public $name = '复制';

    public function handle(Channel $model)
    {
        $air = ChannelGenerator::getLatestAirDate($model->name);
        if(!$air) $air = time();
        $channel_id = $model->id;
        $new = $model->replicate();
        $new->lock_status = Channel::LOCK_EMPTY;
        $new->air_date = date('Y-m-d', $air);
        $new->version = 1;
        $new->distribution_date = null;
        $new->save();
        
        $newid = $new->id;
        $programs = ChannelPrograms::where('channel_id', $channel_id)->get();

        foreach($programs as $pro)
        {
            $pro = $pro->replicate();
            $pro->channel_id = $newid;
            $pro->save();
        }

        CalculationEvent::dispatch($newid);

        return $this->response()->success(__('Replicate Success message'))->refresh();
    }

}