<?php
namespace App\Admin\Actions\Channel;

use App\Models\Channel;
use App\Models\Material;
use App\Tools\Exporter\XmlReader;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CheckXml implements Renderable
{
    public function render($key = null)
    {
        $ch = Channel::find($key);
        $label = '';
        if(!$ch) $data = '<tr><td>播出编单不存在</td></tr>';
        else {
            if(Storage::disk('xml')->exists($ch->name.'_'.$ch->air_date.'.xml')) 
            {
                $file = Storage::disk('xml')->get($ch->name.'_'.$ch->air_date.'.xml');
                
                $items = XmlReader::parseXml($file);
    
                $list = DB::table('material')->whereIn('unique_no', array_unique($items))
                            ->where('status', '<>', Material::STATUS_READY)->select(['name','unique_no','duration'])
                            ->get();
                $data = '<tr><th>'.__('Name').'</th><th>'.__('Unique no').'</th><th>'.__('Duration').'</th><th></th></tr>';
                if(count($list)) {
                    foreach($list as $m)
                    {
                        $data .= '<tr><td>'.$m->name.'</td><td>'.$m->unique_no.'</td><td>'.$m->duration.'</td><td>物料缺失</td></tr>';
                    }
                    $label = '<p>播出编单:'.Channel::GROUPS[$ch->name].' 日期:'.$ch->air_date.' 检查结果：<span class="label label-danger">不通过</span></p>';
                }
                else {
                    $label = '<p>播出编单:'.Channel::GROUPS[$ch->name].' 日期:'.$ch->air_date.' 检查结果：<span class="label label-success">通过</span></p>';
                }
            }
            else {
                $label = '<p>播出编单:'.$ch->name.'_'.$ch->air_date.'.xml 文件不存在</p>';
                $data = '';
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