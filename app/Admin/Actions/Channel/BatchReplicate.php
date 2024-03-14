<?php

namespace App\Admin\Actions\Channel;

use App\Events\Channel\CalculationEvent;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Tools\ChannelGenerator;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchReplicate extends BatchAction
{
    public $name = '批量复制';

    public function handle(Collection $collection)
    {
        $air = false; 
        foreach ($collection as $model) {
            if(!$air) $air = ChannelGenerator::getLatestAirDate($model->name);
            if(!$air) $air = time();
            $channel_id = $model->id;
            $new = $model->replicate();
            $new->audit_status = Channel::AUDIT_EMPTY;
            $new->air_date = date('Y-m-d', $air);
            $new->version = 1;
            $new->distribution_date = null;
            $air += 86400;

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

        }

        return $this->response()->success(__('BatchReplicate Success message'))->refresh();
    }

}