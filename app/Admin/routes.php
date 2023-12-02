<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Encore\Admin\Facades\Admin;
use App\Models\Program;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');

    $router->resource('/media/category', 'Media\\CategoryController')->names('media.category');
    $router->resource('/records', 'RecordsController')->names('records');
    $router->post('/media/material/unique', 'Media\\MaterialController@unique')->name('media.material.unique');
    $router->resource('/media/material', 'Media\\MaterialController')->names('media.material');
    $router->post('/media/programs/unique', 'Media\\ProgramController@unique')->name('media.material.unique');
    $router->resource('/media/programs', 'Media\\ProgramController')->names('media.program');
    $router->resource('/media/blacklist', 'Media\\BlackListController')->names('media.blacklist');

    $router->get('/channel/xkc/tree/{id}', 'Channel\\XkcProgramController@tree')->name('channel.xkc.programs.tree');
    $router->resource('/channel/xkc/programs', 'Channel\\XkcProgramController')->names('channel.xkc.programs');
    $router->resource('/channel/xkc', 'Channel\\XkcController')->names('channel.xkc');
    $router->delete('/channel/xkc/data/{id}/remove/{idx}', 'Channel\\XkcProgramController@remove')->name('channel.xkc.programs.delete');
    $router->post('/channel/xkc/data/{id}/save', 'Channel\\XkcProgramController@save')->name('channel.xkc.programs.save');

    $router->get('/channel/xkv/tree/{id}', 'Channel\\XkvProgramController@tree')->name('channel.xkv.programs.tree');
    $router->resource('/channel/xkv/programs', 'Channel\\XkvProgramController')->names('channel.xkv.programs');
    $router->resource('/channel/xkv', 'Channel\\XkvController')->names('channel.xkv');
    $router->delete('/channel/xkv/data/{id}/remove/{idx}', 'Channel\\XkvProgramController@remove')->name('channel.xkv.programs.delete');
    $router->post('/channel/xkv/data/{id}/save', 'Channel\\XkvProgramController@save')->name('channel.xkv.programs.save');

    $router->get('/template/xkc/tree/{id}', 'Template\\XkcProgramsController@tree')->name('template.xkc.programs.tree');
    $router->post('/template/xkc/data/{id}/save', 'Template\\XkcProgramsController@save')->name('template.xkc.programs.save');
    $router->delete('/template/xkc/data/{id}/remove/{idx}', 'Template\\XkcProgramsController@remove')->name('template.xkc.programs.delete');
    
    $router->resource('/template/xkc/programs', 'Template\\XkcProgramsController')->names('template.xkc.programs');
    $router->resource('/template/xkc', 'Template\\XkcController')->names('template.xkc');

    $router->get('/template/xkv/tree/{id}', 'Template\\XkvProgramsController@tree')->name('template.xkv.tree');
    $router->post('/template/xkv/data/{id}/save', 'Template\\XkvProgramsController@save')->name('template.xkv.tree.save');
    $router->delete('/template/xkv/data/{id}/remove/{idx}', 'Template\\XkvProgramsController@remove')->name('template.xkv.tree.delete');
    
    $router->resource('/template/xkv/programs', 'Template\\XkvProgramsController')->names('template.xkv.programs');
    $router->resource('/template/xkv', 'Template\\XkvController')->names('template.xkv');

    $router->get('/export/download/{id}', 'ExportListController@download')->name('export.download');
    $router->resource('/export/list', 'ExportListController')->names('export.list');
    
    
    $router->get('/api/tree/programs', function (Request $request) {
        $q = $request->get('q');
        $p = (int)$request->get('p', 1);
        $o = (int)$request->get('o', 1);
        $size = 20;$start = ($p-1)*$size;

        $model = new Program();
        if($o) {
            $model = $model->where('category', 'like', "%$q%");
        }
        else {
            $model = $model->where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%");
        }

        return response()->json([
            'total' => $model->count(),
            'result'=> $model->select(DB::raw('id, unique_no, duration, name, category, artist, black'))->orderByDesc('id')->offset($start)
                ->limit($size)->get()->toArray()
            ]);      
        // return Program::where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%")
        //     ->select(DB::raw('id, concat(unique_no, " ", name, " ", artist) as text, unique_no, duration, name, category, artist, black'))
        //     ->paginate(15);
    });
    $router->get('/api/programs', function (Request $request) {
        $q = $request->get('q');
            
        return DB::table('program')->where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%")
            ->select(DB::raw('`unique_no` as id, concat(unique_no, " ", name, " ", artist) as text'))
            ->paginate(15);
    });
    $router->get('/api/category', function (Request $request) {
        $q = $request->get('q');
    
        return DB::table('category')->where('no', 'like', "$q%")->where('type', 'tags')
            ->select(DB::raw('`no` as id, concat("【 ",no, " 】 ", name) as text'))
            ->paginate(15);
    });
});
