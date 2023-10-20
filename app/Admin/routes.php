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

    $router->resource('/category', 'CategoryController')->names('category');
    $router->resource('/records', 'RecordsController')->names('records');
    $router->resource('/material', 'MaterialController')->names('material');
    $router->resource('/programs', 'ProgramController')->names('program');
    $router->resource('/channel', 'ChannelController')->names('channel');

    $router->resource('/template', 'TemplateController')->names('template');
    $router->resource('/template/programs', 'TemplateProgramsController')->names('template.programs');

});
