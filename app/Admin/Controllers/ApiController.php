<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Tools\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function notifications()
    {
        return response()->json(Notify::readDBNotifications());
    }

    public function mediainfo(Request $request)
    {
        return Cache::get('mediainfo_'.$request->get('unique_no'));
    }

    public function treePrograms(Request $request) {
        $q = $request->get('q');
        $p = (int)$request->get('p', 1);
        $t = $request->get('t', 'program');
        $c = $request->get('c');
        $s = $request->get('s');
        $q = substr($q, 0, 20);
        $size = 20;$start = ($p-1)*$size;

        $model = DB::table('program');

        if($q)
            $model = $model->where('name', 'like', "$q")->orWhere('unique_no', 'like', "$q")->orWhere('artist', 'like', "$q");
        if($c) {
            $model = $model->where('category', 'like', "%$c,%");
        }
        if($s) {
            $model = $model->where('seconds', '<=', $s);
        }

        if($t == 'program')
            $sql = 'id, unique_no, duration, name, category, artist, 1 as ep, black';
        else
            $sql = 'id, unique_no, duration, name, category, episodes as artist, ep, black';

        return response()->json([
            'total' => $model->count(),
            //'sql' => $model->dump(),
            'result'=> $model->select(DB::raw($sql))->orderByDesc('seconds')->offset($start)
                ->limit($size)->get()->toArray()
            ]);      

    }

    public function records(Request $request) {
        $q = $request->get('q');
        $p = (int)$request->get('p', 1);
        $c = $request->get('c');
        $m = $request->get('m');
        $t = $request->get('t', 'records');
        $size = 20;
        $start = ($p-1)*$size;

        $model = DB::table($t);
        if($t == 'program')
            $sql = 'id, unique_no, duration, name, category, artist, 1 as ep, black';
        else
            $sql = 'id, unique_no, duration, name, category, episodes as artist, ep, black';

        if($c) {
            $model = $model->where('category', 'like', "%$c,%");
        }
        if($m == '剧集名') {
            $model = $model->where('episodes', 'like', "$q");
        }
        if($q) {
            $model = $model->where('name', 'like', "$q")->orWhere('unique_no', 'like', "$q");
        }

        return response()->json([
            'total' => $model->count(),
            //'sql' => $model->dump(),
            'result'=> $model->select(DB::raw($sql))->orderByDesc('seconds')->offset($start)
                ->limit($size)->get()->toArray()
            ]);      

    }

    public function template(Request $request) {
        $group_id = $request->get('q');

        return Template::where('group_id', $group_id)->where('status', Template::STATUS_SYNCING)
            ->orderBy('sort')->get(['id', DB::raw("concat(name,' ', start_at, '-', end_at)  as text")]);
    }

    public function programs(Request $request) {
        $q = $request->get('q');
            
        return DB::table('program')->where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%")
            ->select(DB::raw('`unique_no` as id, concat(unique_no, " ", name, " ", artist) as text'))
            ->orderBy('seconds', 'desc')->paginate(15);
    }

    public function category(Request $request) {
        $q = $request->get('q');
    
        return DB::table('category')->where('no', 'like', "$q%")->where('type', 'tags')
            ->select(DB::raw('`no` as id, concat("【 ",no, " 】 ", name) as text'))
            ->paginate(15);
    }

    public function episode(Request $request) {
        $q = $request->get('q');
    
        $json = DB::table('records')->where('episodes', 'like', "%$q%")
            ->select(DB::raw('distinct(`episodes`) as `text`'))
            ->paginate(15)->toJson();

        $data = json_decode($json, true);

        foreach($data['data'] as &$item)
        {
            $item['id'] = $item['text'];
        }
        return response()->json($data);
    }

    public function episodes(Request $request) {
        
        //$q = $request->get('q');

        $items = DB::table('records')->selectRaw('distinct(episodes)')
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