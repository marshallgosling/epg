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

    $router->resource('/media/category', 'CategoryController')->names('media.category');
    $router->resource('/records', 'RecordsController')->names('records');
    $router->post('/media/material/unique', 'MaterialController@unique')->name('media.material.unique');
    $router->resource('/media/material', 'MaterialController')->names('media.material');
    $router->post('/media/programs/unique', 'ProgramController@unique')->name('media.material.unique');
    $router->resource('/media/programs', 'ProgramController')->names('media.program');
    
    $router->get('/channel/channelv/tree/{id}', 'ChannelProgramsController@tree')->name('channel.channelv.programs.tree');
    $router->resource('/channel/channelv/programs', 'ChannelProgramsController')->names('channel.channelv.programs');
    $router->resource('/channel/channelv', 'ChannelController')->names('channel.channelv');
    $router->delete('/channel/channelv/data/{id}/remove/{idx}', 'ChannelProgramsController@remove')->name('channel.channelv.programs.delete');
    $router->post('/channel/channelv/data/{id}/save', 'ChannelProgramsController@save')->name('channel.channelv.programs.post');

    $router->get('/channel/xkv/tree/{id}', 'Program\\XkvController@tree')->name('channel.xkv.programs.tree');
    $router->resource('/channel/xkv/programs', 'Program\\XkvController')->names('channel.xkv.programs');
    $router->resource('/channel/xkv', 'Channel\\XkvController')->names('channel.xkv');
    $router->delete('/channel/xkv/data/{id}/remove/{idx}', 'Program\\XkvController@remove')->name('channel.xkv.programs.delete');
    $router->post('/channel/xkv/data/{id}/save', 'Program\\XkvController@save')->name('channel.xkv.programs.post');

    $router->get('/template/channelv/tree/{id}', 'TemplateProgramsController@tree')->name('template.channelv.programs.tree');
    $router->post('/template/channelv/tree/{id}/save', 'TemplateProgramsController@save')->name('template.channelv.programs.save');
    $router->delete('/template/channelv/tree/{id}/remove/{idx}', 'TemplateProgramsController@remove')->name('template.channelv.programs.delete');
    
    $router->resource('/template/channelv/programs', 'TemplateProgramsController')->names('template.programs');
    $router->resource('/template/channelv', 'TemplateController')->names('template');

    $router->get('/template/xkv/tree/{id}', 'Template\\XkvProgramsController@tree')->name('template.xkv.tree');
    $router->post('/template/xkv/tree/{id}/save', 'Template\\XkvProgramsController@save')->name('template.xkv.tree.save');
    $router->delete('/template/xkv/tree/{id}/remove/{idx}', 'Template\\XkvProgramsController@remove')->name('template.xkv.tree.delete');
    
    $router->resource('/template/xkv/programs', 'Template\\XkvProgramController')->names('template.xkv.programs');
    $router->resource('/template/xkv', 'Template\\XkvController')->names('template.xkv');

    $router->get('/export/download/{id}', 'ExportListController@download')->name('export.download');
    $router->resource('/export/list', 'ExportListController')->names('export.list');
    
    $router->resource('/media/blacklist', BlackListController::class)->names('tools.blacklist');

    $router->get('/api/programs', function (Request $request) {
        $q = $request->get('q');
        return response()->json(['result'=>Program::where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q%")
                ->select(DB::raw('id, unique_no, duration, name, category, artist, black'))->orderByDesc('id')
                ->limit(20)->get()->toArray()]);      
        // return Program::where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->orWhere('artist', 'like', "%$q")
        //     ->select(DB::raw('id, concat(unique_no, " ", name, " ", artist) as text, unique_no, duration, name, category, artist, black'))
        //     ->paginate(15);
    });
    $router->get('/api/category', function (Request $request) {
        $q = $request->get('q');
    
        return Category::where('no', 'like', "$q%")->where('type', 'channel')
            ->select(DB::raw('id, concat("【 ",no, " 】 ", name) as text, no as category,  name, duration'))
            ->paginate(15);
    });
});
