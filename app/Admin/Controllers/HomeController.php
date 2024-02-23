<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Program;
use App\Models\Record;
use App\Models\Record2;
use App\Tools\Exporter\TableGenerator;
use App\Tools\Statistic;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HomeController extends Controller
{
  
  public const VERSION = '1.9';
  
  public function index(Content $content)
  {
    return $content
            ->title('欢迎使用编单及节目管理系统')
            ->description('查看、管理频道编单及节目内容和素材管理')
            ->row(HomeController::links())
            ->row(HomeController::statistics())
            ->row(function (Row $row) {

                $row->column(8, function (Column $column) {
                    $column->append(HomeController::charts());
                });

                $row->column(4, function (Column $column) {
                    $column->append(HomeController::environment());
                });

                
            });
    }

    public function preview(Content $content, Request $request)
    {
        
        $generator = new TableGenerator('xkc');
        $st = strtotime($request->get('start_at', ''));
        $ed = strtotime($request->get('end_at', ''));
        $label_st = '';
        $label_ed = '';

        if($st=='') $table = '<p>请选择日期范围</p>';
        else {
          $tmp = $st + 6 * 86400;
        if($ed>$tmp) $ed = $tmp;
        if($ed<$st) $ed = $tmp;

        $label_st = date('Y-m-d', $st);
        $label_ed = date('Y-m-d', $ed);

        $days = $generator->generateDays($st, $ed);
        $data = $generator->processData($days);
        $template = $generator->loadTemplate();
        $table = $generator->export($days, $template, $data);
        }

        $filter= <<<FILTER
        <div class="box-header with-border filter-box" id="filter-box">
    <form action="export" class="form-horizontal" pjax-container method="get">

        <div class="row">
                        <div class="col-md-8">
                <div class="box-body">
                    <div class="fields-group">
                                                    <div class="form-group">
    <label class="col-sm-2 control-label">时间范围</label>
    <div class="col-sm-8">
        <div class="input-group input-group-sm">
            <div class="input-group-addon">
                <i class="fa fa-calendar"></i>
            </div>
            <input type="text" class="form-control" id="start_at_start" placeholder="时间范围" name="start_at" value="{$label_st}" autocomplete="off">

            <span class="input-group-addon" style="border-left: 0; border-right: 0;">-</span>

            <input type="text" class="form-control" id="start_at_end" placeholder="时间范围" name="end_at" value="{$label_ed}" autocomplete="off">
        </div>
    </div>
    <div class="col-sm-2">
      <div class="btn-group pull-left">
                            <button class="btn btn-info submit btn-sm"><i class="fa fa-search"></i>  搜索</button>
                        </div>
    </div>
</div>
                                            </div>
                </div>
            </div>
                    </div>
        <!-- /.box-body -->

    </form>
</div>
FILTER;
        
        $head = '<div class="box-header with-border clearfix">选择开始日期及结束日期（可选），一次最多展示7天</div>';
        $box = '<div class="col-md-12"><div class="box box grid-box">'.$filter.$head.'<div class="box-body table-responsive no-padding">'.$table.'</div></div></div>';
        
        return $content
            ->title('星空中国节目单查看工具')
            ->description('')
            ->row($box);
    
    }

    public function help(Content $content)
    {
        return $content
            ->title('素材入库帮助文档')
            ->description('Help')
            ->row('<div class="embed-responsive embed-responsive-4by3"><iframe class="embed-responsive-item" src="'.config('SUPERVISOR_URL', 'http://127.0.0.1:9101').'"></iframe></div>');
   
    }

    public function supervisord(Content $content)
    {
        return $content
            ->title('Supervisord 管理工具')
            ->description('查看和管理 Laravel Queue 队列进程')
            ->row('<div class="embed-responsive embed-responsive-4by3"><iframe class="embed-responsive-item" src="'.config('SUPERVISOR_URL', 'http://127.0.0.1:9101').'"></iframe></div>');
    }

    public static function charts()
    {
      $material = Statistic::generatePieChart('materialChart', Program::STATUS, Statistic::countMaterial(),'素材库');
      $program = Statistic::generatePieChart('programChart', Program::STATUS, Statistic::countPrograms(),'V China 节目库');
      $records = Statistic::generatePieChart('recordsChart', Program::STATUS, Statistic::countRecords(),'星空中国 节目库');
      $record2 = Statistic::generatePieChart('record2Chart', Program::STATUS, Statistic::countRecord2(),'星空国际节目库');

        $html = <<<HTML
       <script src="/vendor/laravel-admin/chartjs/chart.js"></script>

<div class="row" style="height:390px">
  <div class="col-md-6"><canvas id="materialChart"></canvas></div>
  <div class="col-md-4">
    <div class="row"><canvas id="programChart"></canvas></div>
    <div class="row"><canvas id="recordsChart"></canvas></div>
    <div class="row"><canvas id="record2Chart"></canvas></div>
  </div>
</div>


<script>
  const colors=[
    'rgb(255, 99, 132)',
    'rgb(54, 162, 235)',
    'rgb(255, 205, 86)',
    'rgb(255, 159, 64)',
    'rgb(153, 102, 255)',
    'rgb(201, 203, 207)'
  ];
  {$material}
  {$program}
  {$records}
  {$record2}

</script>
HTML;

        
        $box = new Box('图表数据', $html);

        $box->style('default');

        $box->solid();

        return $box->render();
    }

    public static function statistics()
    {
      $templates = Statistic::countTemplate();
      $channels = Statistic::countChannelXml();
      $audit = Statistic::countAudit();
      
      foreach(['xkv','xkc','xki'] as $k) {
        if(!array_key_exists($k, $channels)) $channels[$k] = 0;
        if(!array_key_exists($k, $audit)) $audit[$k] = 0;
        if(!array_key_exists($k, $templates)) $templates[$k] = 0;
      }

      $channels =[
        '频道 V China' => [
          '节目库内容数量 <span class="badge">'.array_sum(Statistic::countPrograms()).'</span>' => admin_url('media/xkv'),
          '模版库数量 <span class="badge">'.$templates['xkv'].'</span>' => admin_url('template/xkv'),
          '编单数量 <span class="badge">'.$channels['xkv'].'</span>' => admin_url('channel/xkv'),
          '已审核编单 <span class="badge">'.$audit['xkv'].'</span>' => admin_url('channel/xkv'),
        ],
        '频道 星空中国' => [
          '节目库内容数量 <span class="badge">'.array_sum(Statistic::countRecords()).'</span>' => admin_url('media/xkc'),
          '模版库数量 <span class="badge">'.$templates['xkc'].'</span>' => admin_url('template/xkc'),
          '编单数量 <span class="badge">'.$channels['xkc'].'</span>' => admin_url('channel/xkc'),
          '已审核编单 <span class="badge">'.$audit['xkc'].'</span>' => admin_url('channel/xkc'),
        ],
        '频道 星空国际' => [
          '节目库内容数量 <span class="badge">'.array_sum(Statistic::countRecord2()).'</span>' => admin_url('media/xki'),
          '模版库数量 <span class="badge">'.$templates['xki'].'</span>' => admin_url('template/xki'),
          '编单数量 <span class="badge">'.$channels['xki'].'</span>' => admin_url('channel/xki'),
          '已审核编单 <span class="badge">'.$audit['xki'].'</span>' => admin_url('channel/xki'),
        ]
      ];

      return view('admin.dashboard.statistic', compact('channels'));
    }

    public static function links() {
        $links = [
            '物料管理' => admin_url('media/material'),
            '黑名单' => admin_url('media/blacklist'),
            '播出计划' => admin_url('plans'),
            '串联单' => admin_url('epg'),
            '通知' => admin_url('notifications'),
            'Excel' => admin_url('export/excel')
        ];

        $title = '';
        return view('admin.dashboard.links', compact('title', 'links'));
    }

    public static function environment()
    {
        $envs = [
            ['name' => 'App version',       'value' => self::VERSION],
            ['name' => 'Laravel version',   'value' => app()->version()],
            ['name' => 'Admin version',     'value' => \Encore\Admin\Admin::VERSION],
            ['name' => 'Server',            'value' => Arr::get($_SERVER, 'SERVER_SOFTWARE')],
            ['name' => 'Cache driver',      'value' => config('cache.default')],
            ['name' => 'Session driver',    'value' => config('session.driver')],
            ['name' => 'Queue driver',      'value' => config('queue.default')],
            ['name' => 'Timezone',          'value' => config('app.timezone')],
            ['name' => 'Locale',            'value' => config('app.locale')],
            ['name' => 'Env',               'value' => config('app.env')],
            //['name' => 'URL',               'value' => config('app.url')],
        ];
        $data = '';
        foreach($envs as $env) {
            $data .= '<tr><td width="120px">'.$env['name'].'</td><td>'.$env['value'].'</td></tr>';
        }
            
        $html = <<<HTML
        <div class="table-responsive">
            <table class="table table-striped">
                {$data}
            </table>
        </div>
HTML;
        $box = new Box('系统环境', $html);

        $box->style('default');

        $box->solid();

        return $box->render();

    }

}
