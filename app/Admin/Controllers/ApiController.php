<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Tools\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function notifications()
    {
        return response()->json(Notify::readNotifications());
    }

    public function treePrograms(Request $request) {
        $q = $request->get('q');
        $p = (int)$request->get('p', 1);
        $o = (int)$request->get('o', 0);
        $size = 20;$start = ($p-1)*$size;

        $model = DB::table('program');
        if($o) {
            $model = $model->where('category', 'like', "%$q%");
        }
        else {
            $model = $model->where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%");
        }

        return response()->json([
            'total' => $model->count(),
            //'sql' => $model->dump(),
            'result'=> $model->select(DB::raw('id, unique_no, duration, name, category, artist, black'))->orderByDesc('id')->offset($start)
                ->limit($size)->get()->toArray()
            ]);      

    }

    public function records(Request $request) {
        $q = $request->get('q');
        $p = (int)$request->get('p', 1);
        $o = (int)$request->get('o', 0);
        $size = 20;$start = ($p-1)*$size;

        if($o == 1) {
            $model = DB::table('records');
            $sql = 'id, unique_no, duration, name, category, episodes as artist, ep, black';
        }
        else {
            $model = DB::table('program');
            $sql = 'id, unique_no, duration, name, category, artist, black';
        }
            
        // if($o) {
        //     $model = $model->where('category', 'like', "%$q%");
        // }
        // else {
            $model = $model->where('name', 'like', "%$q%")->orWhere('category', 'like' ,"%$q%")->orWhere('unique_no', 'like', "$q%");
            if($o == 0) {
                $model = $model->orWhere('artist', 'like', "%$q%");
            }
        // }

        return response()->json([
            'total' => $model->count(),
            //'sql' => $model->dump(),
            'result'=> $model->select(DB::raw($sql))->orderByDesc('id')->offset($start)
                ->limit($size)->get()->toArray()
            ]);      

    }


    public function programs(Request $request) {
        $q = $request->get('q');
            
        return DB::table('program')->where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%")
            ->select(DB::raw('`unique_no` as id, concat(unique_no, " ", name, " ", artist) as text'))
            ->paginate(15);
    }

    public function category(Request $request) {
        $q = $request->get('q');
    
        return DB::table('category')->where('no', 'like', "$q%")->where('type', 'tags')
            ->select(DB::raw('`no` as id, concat("【 ",no, " 】 ", name) as text'))
            ->paginate(15);
    }

    public function episodes(Request $request) {
        
        $q = $request->get('q');

        $items = DB::table('records')->where('category', 'like', "%$q%")->selectRaw('distinct(episodes)')
                ->orderBy('episodes')->get()->toArray();

        $list = [];
        foreach($items as $item)
        {
            if($item->episodes)
                $list[] = ['id'=>$item->episodes, 'text'=>$item->episodes];
        }

        return response()->json($list);
    }

}