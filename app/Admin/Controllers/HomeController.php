<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Tools\Statistic;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Support\Arr;

class HomeController extends Controller
{
  
  public const VERSION = '1.8';
  
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

    public function supervisord(Content $content)
    {
        return $content
            ->title('Supervisord 管理工具')
            ->description('查看和管理 Laravel Queue 队列进程')
            ->row('<div class="embed-responsive embed-responsive-4by3"><iframe class="embed-responsive-item" src="'.config('SUPERVISOR_URL', 'http://127.0.0.1:9101').'"></iframe></div>');
    }

    public static function charts()
    {
        $html = <<<HTML
       <script src="/vendor/laravel-admin/chartjs/chart.js"></script>

<div style="height:390px">
  <canvas id="myChart"></canvas>
</div>

<script>
  const ctx = document.getElementById('myChart');

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['周一', '周二', '周三', '周四', '周五', '周六'],
      datasets: [{
        label: '更新节目',
        data: [12, 19, 3, 5, 2, 3],
        borderWidth: 1
      },
        {
            label: '更新素材',
            data: [10, 13, 9, 10, 6, 8],
            borderWidth: 1,
            type: 'bar'
        }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
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
          '节目库内容数量 <span class="badge">'.Statistic::countPrograms().'</span>' => admin_url('media/xkv'),
          '模版库数量 <span class="badge">'.$templates['xkv'].'</span>' => admin_url('template/xkv'),
          '编单数量 <span class="badge">'.$channels['xkv'].'</span>' => admin_url('channel/xkv'),
          '已审核编单 <span class="badge">'.$audit['xkv'].'</span>' => admin_url('channel/xkv'),
        ],
        '频道 星空中国' => [
          '节目库内容数量 <span class="badge">'.Statistic::countRecords().'</span>' => admin_url('media/xkc'),
          '模版库数量 <span class="badge">'.$templates['xkc'].'</span>' => admin_url('template/xkc'),
          '编单数量 <span class="badge">'.$channels['xkc'].'</span>' => admin_url('channel/xkc'),
          '已审核编单 <span class="badge">'.$audit['xkc'].'</span>' => admin_url('channel/xkc'),
        ],
        '频道 星空国际' => [
          '节目库内容数量 <span class="badge">'.Statistic::countRecord2().'</span>' => admin_url('media/xki'),
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
