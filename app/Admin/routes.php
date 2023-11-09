<?php

use Illuminate\Routing\Router;

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
    $router->resource('/channel/channelv', 'ChannelController')->names('channel.channelv');
    $router->resource('/channel/channelv/programs', 'ChannelProgramsController')->names('channel.channelv.programs');

    $router->resource('/template/channelv/programs', 'TemplateProgramsController')->names('template.programs');
    $router->resource('/template/channelv', 'TemplateController')->names('template');
    
});
