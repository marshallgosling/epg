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
    
    $router->get('/channel/xkc/tree/{id}', 'Program\\XkcController@tree')->name('channel.xkc.programs.tree');
    $router->resource('/channel/xkc/programs', 'Program\\XkcController')->names('channel.xkc.programs');
    $router->resource('/channel/xkc', 'Channel\\XkcController')->names('channel.xkc');
    $router->delete('/channel/xkc/data/{id}/remove/{idx}', 'Program\\XkcController@remove')->name('channel.xkc.programs.delete');
    $router->post('/channel/xkc/data/{id}/save', 'Program\\XkcController@save')->name('channel.xkc.programs.save');

    $router->get('/channel/xkv/tree/{id}', 'Program\\XkvController@tree')->name('channel.xkv.programs.tree');
    $router->resource('/channel/xkv/programs', 'Program\\XkvController')->names('channel.xkv.programs');
    $router->resource('/channel/xkv', 'Channel\\XkvController')->names('channel.xkv');
    $router->delete('/channel/xkv/data/{id}/remove/{idx}', 'Program\\XkvController@remove')->name('channel.xkv.programs.delete');
    $router->post('/channel/xkv/data/{id}/save', 'Program\\XkvController@save')->name('channel.xkv.programs.save');

    $router->get('/template/xkc/tree/{id}', 'Template\\XkcProgramsController@tree')->name('template.xkc.programs.tree');
    $router->post('/template/xkc/tree/{id}/save', 'Template\\XkcProgramsController@save')->name('template.xkc.programs.save');
    $router->delete('/template/xkc/tree/{id}/remove/{idx}', 'Template\\XkcProgramsController@remove')->name('template.xkc.programs.delete');
    
    $router->resource('/template/xkc/programs', 'Template\\XkcProgramsController')->names('template.xkc.programs');
    $router->resource('/template/xkc', 'Template\\XkcController')->names('template.xkc');

    $router->get('/template/xkv/tree/{id}', 'Template\\XkvProgramsController@tree')->name('template.xkv.tree');
    $router->post('/template/xkv/tree/{id}/save', 'Template\\XkvProgramsController@save')->name('template.xkv.tree.save');
    $router->delete('/template/xkv/tree/{id}/remove/{idx}', 'Template\\XkvProgramsController@remove')->name('template.xkv.tree.delete');
    
    $router->resource('/template/xkv/programs', 'Template\\XkvProgramController')->names('template.xkv.programs');
    $router->resource('/template/xkv', 'Template\\XkvController')->names('template.xkv');

    $router->get('/export/download/{id}', 'ExportListController@download')->name('export.download');
    $router->resource('/export/list', 'ExportListController')->names('export.list');
    
    $router->resource('/media/blacklist', BlackListController::class)->names('tools.blacklist');

    $router->get('/api/tree/programs', function (Request $request) {
        $q = $request->get('q');
        $p = (int)$request->get('p', 1);
        $size = 20;$start = ($p-1)*$size;
        return response()->json([
            'total' => Program::where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%")->count(),
            'result'=> Program::where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%")
                ->select(DB::raw('id, unique_no, duration, name, category, artist, black'))->orderByDesc('id')->offset($start)
                ->limit($size)->get()->toArray()
            ]);      
        // return Program::where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%")
        //     ->select(DB::raw('id, concat(unique_no, " ", name, " ", artist) as text, unique_no, duration, name, category, artist, black'))
        //     ->paginate(15);
    });
    $router->get('/api/programs', function (Request $request) {
        $q = $request->get('q');
            
        return Program::where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%")
            ->select(DB::raw('id, concat(unique_no, " ", name, " ", artist) as text, unique_no, duration, name, category, artist, black'))
            ->paginate(15);
    });
    $router->get('/api/category', function (Request $request) {
        $q = $request->get('q');
    
        return Category::where('no', 'like', "$q%")->where('type', 'tags')
            ->select(DB::raw('id, concat("【 ",no, " 】 ", name) as text, no as category,  name, duration'))
            ->paginate(15);
    });
});
