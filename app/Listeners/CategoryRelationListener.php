<?php

namespace App\Listeners;

use App\Events\CategoryRelationEvent;
use App\Models\Category;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class CategoryRelationListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\CategoryRelationEvent  $event
     * @return void
     */
    public function handle(CategoryRelationEvent $event)
    {
        $program_id = $event->program_id;
        $table = $event->table;
        $categorys = $event->categorys;

        if(is_array($categorys) && count($categorys))
        {
            $cids = Category::whereIn('no', $categorys)->pluck('id')->toArray();
            $data = [];
            foreach($cids as $cid)
            {
                $data[] = ['category_id'=>$cid, 'record_id'=>$program_id, 'type'=>Category::TYPE_TAGS];
            }

            if(count($data)) {
                DB::table('category_'.$table)->where(['record_id'=>$program_id,'type'=>Category::TYPE_TAGS])->delete();
                DB::table('category_'.$table)->insert($data);
            }
        }
    }
}
