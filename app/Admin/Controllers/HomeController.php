<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Material;
use App\Tools\Notify;
use App\Tools\Statistic;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HomeController extends Controller
{
  
  public const VERSION = '2.2';
  
  public function index(Content $content)
  {
    return $content
            ->title('欢迎使用编单及节目管理系统 v'.self::VERSION)
            ->description('查看、管理频道编单及节目内容和素材管理')
            ->row(HomeController::links())
            ->row(HomeController::statistics())
            ->row(function (Row $row) {
                $row->column(8, function (Column $column) {
                  $column->append(HomeController::notifyError());
                });
                $row->column(4, function (Column $column) {
                  $column->append(HomeController::distribution());
                });
            })
            ->row(function (Row $row) {
              $row->column(8, function (Column $column) {
                $column->append(HomeController::charts());
              });
              $row->column(4, function (Column $column) {
                $column->append(HomeController::daily());
              });
            });
    }

    private static function distribution()
    {
        $list = Notify::getDistributions();
        $data = '';
        foreach($list as $m) {
            $data .= '<tr><td width="80">'.Channel::GROUPS[$m->group_id].'</td><td>'.
            $m->message.'</td><td width="90">'.substr($m->created_at, 5, 11).'</td></tr>';
        }
            
        $html = <<<HTML
        <div class="row" style="height:390px; overflow-y:scroll">
        <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-striped">
                <tr><th>频道</th><th>消息</th><th>分发日期</th></tr>
                {$data}
            </table>
        </div></div>
      </div>
HTML;
        $box = new Box('分发串联单通知记录', $html);

        $box->style('aqua');
        
        $box->solid();

        return $box->render();
    }

    private static function notifyError()
    {
        $list = Notify::getErrorNotifications();
        $data = '';
        foreach($list as $m) {
            $data .= '<tr><td>'.Channel::GROUPS[$m->group_id].'</td><td>'.
            $m->name.'</td><td><div style="height:40px;overflow-y:scroll">'. $m->message.'</div>'.
            '</td><td>'.substr($m->created_at, 5, 11).'</td></tr>';
        }
            
        $html = <<<HTML
        <div class="row" style="height:390px; overflow-y:scroll">
        <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-striped">
                <tr><th width="100">频道</th><th width="160">标题</th><th>描述内容</th><th width="100">错误日期</th></tr>
                {$data}
            </table>
        </div></div>
      </div>
HTML;
        $box = new Box('错误通知记录', $html);

        $box->style('');
        
        $box->solid();

        return $box->render();
    }

    private static function daily()
    {
        $list = Material::where('created_at','<', date('Y-m-d H:i:s'))->orderBy('created_at', 'desc')->limit(20)->get();
        
        $data = '';
        foreach($list as $m) {
            $data .= '<tr><td>'.$m->name.'</td><td>'.substr($m->created_at, 5, 11).'</td></tr>';
        }
            
        $html = <<<HTML
        <div class="row" style="height:390px; overflow-y:scroll">
        <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-striped">
                {$data}
            </table>
        </div></div>
      </div>
HTML;
        $box = new Box('最新添加物料记录', $html);

        $box->style('');
        
        $box->solid();

        return $box->render();
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
      //$material = Statistic::generatePieChart('materialChart', Program::STATUS, Statistic::countMaterial(),'总物料库');
      $material = Statistic::countMaterial();
      $program = Statistic::countPrograms();
      $records = Statistic::countRecords();
      $record2 = Statistic::countRecord2();

      $channels = Statistic::generateBarChart('programChart', 
        array_merge(['all'=>'总物料库'], Channel::GROUPS), 
        [$material['1'], $program['1'], $records['1'], $record2['1']], "物料库及节目库（可用数）");

        $html = <<<HTML
       <script src="/vendor/laravel-admin/chartjs/chart.js"></script>

<div class="row">
  
  <div class="col-md-12" style="border:0px solid #eee; border-left-width:1px;">
    <div class="row">
      <div class="col-md-12"><canvas id="programChart" style="height:390px"></canvas></div>
    </div>
    
  </div>
</div>


<script>
  const colors=[
    'rgb(75, 192, 192)',
    'rgb(54, 162, 235)',
    'rgb(54, 162, 235)',
    'rgb(54, 162, 235)'
  ];
  const bcolors=[
    'rgba(75, 192, 192, 0.3)',
    'rgba(54, 162, 235, 0.3)',
    'rgba(54, 162, 235, 0.3)',
    'rgba(54, 162, 235, 0.3)'
  ];

  {$channels}

</script>
HTML;

        
        $box = new Box('图表数据', $html);

        $box->style('');

        $box->solid();

        return $box->render();
    }

    public static function statistics()
    {
      $templates = Statistic::countTemplate();
      $channels = Statistic::countChannelXml();
      //$audit = Statistic::countAudit();
      $distribution = Statistic::countDistribution();
      
      foreach(['xkv','xkc','xki'] as $k) {
        if(!array_key_exists($k, $channels)) $channels[$k] = 0;
        //if(!array_key_exists($k, $audit)) $audit[$k] = 0;
        if(!array_key_exists($k, $templates)) $templates[$k] = 0;
        if(!array_key_exists($k, $distribution)) $distribution[$k] = 0;
      }

      $channels =[
        '频道 V China' => [
          '节目库内容数量 <span class="badge">'.array_sum(Statistic::countPrograms()).'</span>' => admin_url('media/xkv'),
          '模版库数量 <span class="badge">'.$templates['xkv'].'</span>' => admin_url('template/xkv'),
          '编单数量 <span class="badge">'.$channels['xkv'].'</span>' => admin_url('channel/xkv'),
          '已分发编单 <span class="badge">'.$distribution['xkv'].'</span>' => admin_url('channel/xkv'),
        ],
        '频道 星空中国' => [
          '节目库内容数量 <span class="badge">'.array_sum(Statistic::countRecords()).'</span>' => admin_url('media/xkc'),
          '模版库数量 <span class="badge">'.$templates['xkc'].'</span>' => admin_url('template/xkc'),
          '编单数量 <span class="badge">'.$channels['xkc'].'</span>' => admin_url('channel/xkc'),
          '已分发编单 <span class="badge">'.$distribution['xkc'].'</span>' => admin_url('channel/xkc'),
        ],
        '频道 星空国际' => [
          '节目库内容数量 <span class="badge">'.array_sum(Statistic::countRecord2()).'</span>' => admin_url('media/xki'),
          '模版库数量 <span class="badge">'.$templates['xki'].'</span>' => admin_url('template/xki'),
          '编单数量 <span class="badge">'.$channels['xki'].'</span>' => admin_url('channel/xki'),
          '已分发编单 <span class="badge">'.$distribution['xki'].'</span>' => admin_url('channel/xki'),
        ]
      ];

      return view('admin.dashboard.statistic', compact('channels'));
    }

    public static function links() {
        $links = [
            '物料管理' => admin_url('media/material'),
            '黑名单' => admin_url('media/blacklist'),
            '翻译对照表' => admin_url('media/keywords'),
            '广告投放' => admin_url('plan/advertise'),
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
            $data .= '<tr><td width="120px">'.$env['name'].'</td><td>'.explode(' ', $env['value'])[0].'</td></tr>';
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
