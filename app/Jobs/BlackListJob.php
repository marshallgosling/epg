<?php

namespace App\Jobs;

use App\Models\BlackList;
use App\Models\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BlackListJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Job ID;
    private $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function uniqueId()
    {
        return $this->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $channels = DB::table('channel')->whereBetween('air_date', [$start_at, $end_at])
        //                                 ->select('id','air_date','uuid')
        //                                 ->orderBy('air_date')->get();
        $blacklist = BlackList::get()->pluck('keyword');
        $channels = Channel::where('air_date', '>', date('Y/m/d'))->with('programs')->select('id','air_date','uuid')->get();
        $data = [];
        foreach($channels as $channel)
        {
            $programs = $channel->programs();

            foreach($programs as $pro)
            {
                $items = json_decode($pro->data);

                foreach($items as $item) {

                    foreach($blacklist as $black) {
                        if(Str::contains($item->artist, $black))
                        {
                            $data[] = [
                                "channel" => ["id"=>$channel->id, "date"=>$channel->air_date],
                                "program" => ["id"=>$pro->id,"name"=>$pro->name,"start_at"=>$pro->start_at],
                                "item" => $item
                            ];
                            break;
                        }
                    }
                }
            }
        }

        $model = BlackList::find($this->id);
        $model->status = BlackList::STATUS_READY;
        $model->data = json_encode($data);
        $model->save();
    }

    /**
     * Get the cache driver for the unique job lock.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function uniqueVia()
    {
        return Cache::driver('redis');
    }
}
