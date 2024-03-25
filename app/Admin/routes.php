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
    $router->get('/supervisord', 'HomeController@supervisord')->name('supervisord');
    
    $router->get('/media/scan/result', 'Media\\MaterialController@compare')->name('media.result.d');
    $router->get('/media/help', 'HelpController@material')->name('media.help');
    $router->get('/media/recognize', 'Media\\ProcessMaterialController@local')->name('media.recognize');
    $router->post('/media/recognize', 'Media\\ProcessMaterialController@process')->name('media.recognize.process');
    $router->resource('/media/agreement', 'AgreementController')->names('media.agreement');
    $router->resource('/media/compare/xkv', 'Media\\XkvCompareController')->names('media.compare.xkv');
    $router->resource('/media/compare/xkc', 'Media\\XkcCompareController')->names('media.compare.xkc');
    $router->resource('/media/compare/xki', 'Media\\XkiCompareController')->names('media.compare.xki');
    $router->get('/media/folder/{id}', 'Media\\ProcessMaterialController@index')->name('media.folder');
    $router->resource('/media/folders', 'Media\\FolderController')->names('media.folders');
    $router->resource('/media/folders/files', 'Media\\RawFilesController')->names('media.folders.files');

    $router->resource('/media/expiration', 'Media\\ExpirationController')->names('media.expiration');
    $router->resource('/media/category', 'Media\\CategoryController')->names('media.category');
    $router->resource('/media/large-files', 'Media\\LargeFileController')->names('media.largefile');

    $router->post('/media/xkc/unique', 'Media\\XkcProgramController@unique')->name('media.xkc.unique');
    $router->post('/media/xkv/unique', 'Media\\XkvProgramController@unique')->name('media.xkv.unique');
    $router->post('/media/xki/unique', 'Media\\XkiProgramController@unique')->name('media.xki.unique');
    $router->resource('/media/xkc', 'Media\\XkcProgramController')->names('media.xkc');
    $router->resource('/media/xki', 'Media\\XkiProgramController')->names('media.xki');
    $router->resource('/media/xkv', 'Media\\XkvProgramController')->names('media.xkv');
    $router->get('/media/material/result', 'Media\\MaterialController@result')->name('media.result');
    
    $router->post('/media/material/unique', 'Media\\MaterialController@unique')->name('media.material.unique');
    $router->resource('/media/material', 'Media\\MaterialController')->names('media.material');
    $router->resource('/media/blacklist', 'Media\\BlackListController')->names('media.blacklist');
    $router->get('/media/blacklist/result/{id}', 'Media\\BlackListController@results')->name('media.blacklist.result');
    $router->post('/media/blacklist/result/{id}/save', 'Media\\BlackListController@saveReplace')->name('media.blacklist.result.save');

    $router->get('/channel/xkc/tree/{id}', 'Channel\\XkcProgramController@tree')->name('channel.xkc.programs.tree');
    $router->get('/channel/xkc/preview/{id}', 'Channel\\XkcController@preview')->name('channel.xkc.preview');
    $router->get('/channel/xkc/export', 'Channel\\XkcController@export')->name('channel.xkc.export');
    $router->resource('/channel/xkc/programs', 'Channel\\XkcProgramController')->names('channel.xkc.programs');
    $router->resource('/channel/xkc', 'Channel\\XkcController')->names('channel.xkc');
    $router->delete('/channel/xkc/data/{id}/remove/{idx}', 'Channel\\XkcProgramController@remove')->name('channel.xkc.programs.delete');
    $router->post('/channel/xkc/data/{id}/save', 'Channel\\XkcProgramController@save')->name('channel.xkc.programs.save');
    
    $router->get('/channel/xki/tree/{id}', 'Channel\\XkiProgramController@tree')->name('channel.xki.programs.tree');
    $router->get('/channel/xki/preview/{id}', 'Channel\\XkiController@preview')->name('channel.xki.preview');
    $router->get('/channel/xki/export', 'Channel\\XkiController@export')->name('channel.xki.export');
    $router->resource('/channel/xki/programs', 'Channel\\XkiProgramController')->names('channel.xki.programs');
    $router->resource('/channel/xki', 'Channel\\XkiController')->names('channel.xki');
    $router->delete('/channel/xki/data/{id}/remove/{idx}', 'Channel\\XkiProgramController@remove')->name('channel.xki.programs.delete');
    $router->post('/channel/xki/data/{id}/save', 'Channel\\XkiProgramController@save')->name('channel.xki.programs.save');
    
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
    $router->post('/template/xkc/reset/state', 'Template\\XkcController@reset')->name('template.xkc.reset');
    $router->post('/template/xkc/restore/state', 'Template\\XkcController@restore')->name('template.xkc.restore');

    $router->get('/template/xki/tree/{id}', 'Template\\XkiProgramsController@tree')->name('template.xki.programs.tree');
    $router->post('/template/xki/data/{id}/save', 'Template\\XkiProgramsController@save')->name('template.xki.programs.save');
    $router->delete('/template/xki/data/{id}/remove/{idx}', 'Template\\XkiProgramsController@remove')->name('template.xki.programs.delete');
    $router->get('/template/xki/preview', 'Template\\XkiController@preview')->name('template.xki.preview');
    $router->resource('/template/xki/programs', 'Template\\XkiProgramsController')->names('template.xki.programs');
    $router->resource('/template/xki', 'Template\\XkiController')->names('template.xki');
    $router->post('/template/xki/reset/state', 'Template\\XkiController@reset')->name('template.xki.reset');
    $router->post('/template/xki/restore/state', 'Template\\XkiController@restore')->name('template.xki.restore');

    $router->get('/template/xkv/tree/{id}', 'Template\\XkvProgramsController@tree')->name('template.xkv.tree');
    $router->post('/template/xkv/data/{id}/save', 'Template\\XkvProgramsController@save')->name('template.xkv.tree.save');
    $router->delete('/template/xkv/data/{id}/remove/{idx}', 'Template\\XkvProgramsController@remove')->name('template.xkv.tree.delete');
    $router->resource('/template/xkv/programs', 'Template\\XkvProgramsController')->names('template.xkv.programs');
    $router->resource('/template/xkv', 'Template\\XkvController')->names('template.xkv');

    $router->get('/template/temp/restore', 'Template\\TempController@restore')->name('template.temp.restore');
    $router->get('/template/simulator/xkc', 'Template\\SimulatorController@xkc')->name('template.simulator.xkc');
    $router->get('/template/simulator/xki', 'Template\\SimulatorController@xki')->name('template.simulator.xki');
    
    $router->resource('/template/temp/programs', 'Template\\TempProgramsController')->names('template.temp.programs');
    $router->get('/template/temp/programs/{program}', 'Template\\TempProgramsController@show')->name('template.temp.show');
    $router->get('/template/help', 'HelpController@template')->name('template.help');

    $router->get('/export/download/{id}', 'ExportListController@download')->name('export.download');
    $router->resource('/export/excel', 'ExportListController')->names('export.list');
    $router->resource('/export/xml', 'ChannelXmlController')->names('export.xml');
    
    $router->resource('/statistic/list', 'StatisticController')->names('statistic.list');
    $router->resource('/notifications', 'NotificationController')->names('notification');
    $router->resource('/plans', 'PlanController')->names('plans');

    $router->resource('/epg/jobs', 'EpgJobController')->names('epg.jobs');
    $router->resource('/epg', 'Channel\\EpgController')->names('channel.epg');
    $router->get('/epg/preview/{air}', 'Channel\\EpgController@preview')->name('channel.preview');

    $router->get('/channel/test/tree/{id}', 'Channel\\TestProgramController@tree')->name('channel.test.programs.tree');
    $router->resource('/channel/test/programs', 'Channel\\TestProgramController')->names('channel.test.programs');
    $router->delete('/channel/test/data/{id}/remove/{idx}', 'Channel\\TestProgramController@remove')->name('channel.test.programs.delete');
    $router->post('/channel/test/data/{id}/save', 'Channel\\TestProgramController@save')->name('channel.test.programs.save');

    $router->resource('/channel/audit', 'AuditController')->names('audit.list');

    $router->get('/api/notifications', 'ApiController@notifications');
    $router->get('/api/tree/programs', 'ApiController@treePrograms');
    $router->get('/api/tree/records', 'ApiController@records');
    $router->get('/api/programs', 'ApiController@programs');
    $router->get('/api/category', 'ApiController@category');
    $router->get('/api/episodes', 'ApiController@episodes');
    $router->get('/api/episode', 'ApiController@episode');
    $router->get('/api/mediainfo', 'ApiController@mediainfo');
});
