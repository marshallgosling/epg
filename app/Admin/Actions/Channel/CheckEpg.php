<?php
namespace App\Admin\Actions\Channel;

use App\Models\Channel;
use App\Tools\ChannelDatabase;
use Illuminate\Contracts\Support\Renderable;

class CheckEpg implements Renderable
{
    
    public function render($key = null)
    {
        $ch = Channel::find($key);
        $data = '';
        $label = '';
        if(!$ch) $data = '<tr><td>播出编单不存在</td></tr>';
        else {
            $data = ChannelDatabase::checkEpgWithChannel($ch);

            if( $data['result']) {
                $label = '<p>播出编单:'.Channel::GROUPS[$ch->name].' 日期:'.$ch->air_date.' 文件:'.$ch->name.'_'.$ch->air_date.'.xml 检查结果：<span class="label label-success">通过</span> 数据一致</p>';
            }
            else  {
                $label = '<p>播出编单:'.Channel::GROUPS[$ch->name].' 日期:'.$ch->air_date.' 文件:'.$ch->name.'_'.$ch->air_date.'.xml 检查结果：<span class="label label-danger">不通过</span> 数据不一致</p>';
                $msg = "<tr><td>播放编单数据和格非串联单xml文件存在差异，请重新“加锁”</td></tr>";
                $msg .= "<tr><td>原因：".$data['msg']."</td></tr>";
                $msg .= "<tr><td>差异数据：</td></tr>";
                $item = $data['items'][0];
                $msg .= "<tr><td>编单数据： {$item['start_at']} - {$item['name']} - {$item['duration']}</td></tr>";
                if(count($data['items']) == 2)
                {
                    $item = $data['items'][1];
                    $msg .= "<tr><td>串联单数据：{$item['start_at']} - {$item['name']} - {$item['duration']}</td></tr>";
                }
            }
           
        }

        $html = <<<HTML
        {$label}
        <div class="table-responsive">
            <table class="table table-striped">
                {$data}
            </table>
        </div>
HTML;
        
        return $html;
    }
    
    
}