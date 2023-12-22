<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Encore\Admin\Facades\Admin;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');

    $router->resource('/media/category', 'Media\\CategoryController')->names('media.category');
    $router->resource('/media/records', 'Media\\RecordController')->names('media.records');
    $router->post('/media/material/unique', 'Media\\MaterialController@unique')->name('media.material.unique');
    $router->resource('/media/material', 'Media\\MaterialController')->names('media.material');
    $router->post('/media/programs/unique', 'Media\\ProgramController@unique')->name('media.material.unique');
    $router->resource('/media/programs', 'Media\\ProgramController')->names('media.program');
    $router->resource('/media/blacklist', 'Media\\BlackListController')->names('media.blacklist');

    $router->get('/channel/xkc/tree/{id}', 'Channel\\XkcProgramController@tree')->name('channel.xkc.programs.tree');
    $router->get('/channel/xkc/preview/{id}', 'Channel\\XkcController@preview')->name('channel.xkc.preview');
    $router->resource('/channel/xkc/programs', 'Channel\\XkcProgramController')->names('channel.xkc.programs');
    $router->resource('/channel/xkc', 'Channel\\XkcController')->names('channel.xkc');
    $router->delete('/channel/xkc/data/{id}/remove/{idx}', 'Channel\\XkcProgramController@remove')->name('channel.xkc.programs.delete');
    $router->post('/channel/xkc/data/{id}/save', 'Channel\\XkcProgramController@save')->name('channel.xkc.programs.save');
    
    $router->post('/channel/open/data/{id}', 'Channel\\XkcProgramController@open')->name('channel.xkc.programs.open');

    $router->get('/channel/xkv/tree/{id}', 'Channel\\XkvProgramController@tree')->name('channel.xkv.programs.tree');
    $router->get('/channel/xkv/preview/{id}', 'Channel\\XkvController@preview')->name('channel.xkv.preview');
    $router->resource('/channel/xkv/programs', 'Channel\\XkvProgramController')->names('channel.xkv.programs');
    $router->resource('/channel/xkv', 'Channel\\XkvController')->names('channel.xkv');
    $router->delete('/channel/xkv/data/{id}/remove/{idx}', 'Channel\\XkvProgramController@remove')->name('channel.xkv.programs.delete');
    $router->post('/channel/xkv/data/{id}/save', 'Channel\\XkvProgramController@save')->name('channel.xkv.programs.save');

    $router->get('/template/xkc/tree/{id}', 'Template\\XkcProgramsController@tree')->name('template.xkc.programs.tree');
    $router->post('/template/xkc/data/{id}/save', 'Template\\XkcProgramsController@save')->name('template.xkc.programs.save');
    $router->delete('/template/xkc/data/{id}/remove/{idx}', 'Template\\XkcProgramsController@remove')->name('template.xkc.programs.delete');
    
    $router->get('/template/xkc/preview', 'Template\\XkcController@preview')->name('template.xkc.preview');
    $router->resource('/template/xkc/programs', 'Template\\XkcProgramsController')->names('template.xkc.programs');
    $router->resource('/template/xkc', 'Template\\XkcController')->names('template.xkc');

    $router->get('/template/xkv/tree/{id}', 'Template\\XkvProgramsController@tree')->name('template.xkv.tree');
    $router->post('/template/xkv/data/{id}/save', 'Template\\XkvProgramsController@save')->name('template.xkv.tree.save');
    $router->delete('/template/xkv/data/{id}/remove/{idx}', 'Template\\XkvProgramsController@remove')->name('template.xkv.tree.delete');
    
    $router->resource('/template/xkv/programs', 'Template\\XkvProgramsController')->names('template.xkv.programs');
    $router->resource('/template/xkv', 'Template\\XkvController')->names('template.xkv');

    $router->get('/template/temp/preview', 'Template\\TempController@preview')->name('template.temp.preview');
    $router->get('/template/simulator', 'Template\\TempController@simulator')->name('template.simulator');
    
    $router->resource('/template/temp/programs', 'Template\\TempProgramsController')->names('template.temp.programs');
    $router->get('/template/temp/programs/{program}', 'Template\\TempProgramsController@show')->name('template.temp.show');
    //$router->resource('/template/temp', 'Template\\TempController')->names('template.temp');
    
    $router->get('/export/download/{id}', 'ExportListController@download')->name('export.download');
    $router->resource('/export/excel', 'ExportListController')->names('export.list');
    $router->resource('/export/xml', 'ChannelXmlController')->names('export.xml');
    
    $router->resource('/statistic/list', 'StatisticController')->names('statistic.list');
    $router->resource('/notifications', 'NotificationController')->names('notification');
    $router->resource('/plans', 'PlanController')->names('plans');
    $router->resource('/epg', 'Channel\\EpgController')->names('channel.epg');
    $router->get('/epg/preview/{air}', 'Channel\\EpgController@preview')->name('channel.preview');
    
    $router->get('/channel/test/tree/{id}', 'Channel\\TestProgramController@tree')->name('channel.test.programs.tree');
    $router->resource('/channel/test/programs', 'Channel\\TestProgramController')->names('channel.test.programs');
    $router->delete('/channel/test/data/{id}/remove/{idx}', 'Channel\\TestProgramController@remove')->name('channel.test.programs.delete');
    $router->post('/channel/test/data/{id}/save', 'Channel\\TestProgramController@save')->name('channel.test.programs.save');

    $router->get('/api/notifications', 'ApiController@notifications');
    $router->get('/api/tree/programs', 'ApiController@treePrograms');
    $router->get('/api/tree/records', 'ApiController@records');
    $router->get('/api/programs', 'ApiController@programs');
    $router->get('/api/category', 'ApiController@category');
    $router->get('/api/episodes', 'ApiController@episodes');
});
