<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Support\Arr;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('欢迎使用编单及节目管理系统')
            ->description('查看、管理频道编单及节目内容和素材管理')
            ->row(HomeController::title())
            ->row(function (Row $row) {

                $row->column(8, function (Column $column) {
                    $column->append(HomeController::charts());
                });

                $row->column(4, function (Column $column) {
                    $column->append(HomeController::environment());
                });

                
            });
    }

    public static function charts()
    {
        $html = <<<HTML
       <script src="/vendor/laravel-admin/chartjs/chart.js"></script>

<div>
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

    public static function channels()
    {
        return '';
    }

    public static function title() {
        $links = [
            '物料管理' => admin_url('/media/material'),
            '节目管理' => admin_url('/media/programs'),
            'XKV' => admin_url('/channel/xkv'),
            '模版库' => admin_url('/template/xkv'),
        ];
        return view('admin.dashboard', ['title'=>'', 'links'=>$links]);
    }

    public static function environment()
    {
        $envs = [
            ['name' => 'PHP version',       'value' => 'PHP/'.PHP_VERSION],
            ['name' => 'Laravel version',   'value' => app()->version()],
            ['name' => 'CGI',               'value' => php_sapi_name()],
            ['name' => 'Uname',             'value' => php_uname()],
            ['name' => 'Server',            'value' => Arr::get($_SERVER, 'SERVER_SOFTWARE')],

            ['name' => 'Cache driver',      'value' => config('cache.default')],
            ['name' => 'Session driver',    'value' => config('session.driver')],
            ['name' => 'Queue driver',      'value' => config('queue.default')],

            ['name' => 'Timezone',          'value' => config('app.timezone')],
            ['name' => 'Locale',            'value' => config('app.locale')],
            //['name' => 'Env',               'value' => config('app.env')],
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
