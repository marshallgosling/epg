<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Encore\Admin\Facades\Admin;
use App\Models\Program;
use Illuminate\Http\Request;

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
    $router->resource('/media/material', 'MaterialController')->names('media.material');
    $router->resource('/media/programs', 'ProgramController')->names('media.program');
    
    $router->get('/channel/channelv/tree/{id}', 'ChannelProgramsController@tree')->name('channel.channelv.programs.tree');
    $router->resource('/channel/channelv/programs', 'ChannelProgramsController')->names('channel.channelv.programs');
    $router->resource('/channel/channelv', 'ChannelController')->names('channel.channelv');
    $router->delete('/channel/channelv/data/{id}/remove/{idx}', 'ChannelProgramsController@remove')->name('channel.channelv.programs.delete');
    $router->post('/channel/channelv/data/{id}/save', 'ChannelProgramsController@save')->name('channel.channelv.programs.post');


    $router->resource('/template/channelv/programs', 'TemplateProgramsController')->names('template.programs');
    $router->resource('/template/channelv', 'TemplateController')->names('template');
    
    $router->get('/api/programs', function (Request $request) {
        $q = $request->get('q');
    
        return Program::where('name', 'like', "%$q%")->orWhere('unique_no', 'like', "$q%")->paginate(null, ['id', 'name as text','category','unique_no','duration']);
    });
});
