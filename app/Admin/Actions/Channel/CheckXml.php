<?php
namespace App\Admin\Actions\Channel;

use App\Models\Channel;
use App\Models\Material;
use App\Tools\Exporter\BvtExporter;
use App\Tools\Exporter\XmlReader;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                $data = BvtExporter::collectEPG($ch);
                BvtExporter::generateData($ch, $data);
                BvtExporter::$file = false;
                $xml = BvtExporter::exportXml($ch->name);

                $str = Storage::disk('xml')->get($ch->name.'_'.$ch->air_date.'.xml');
                if( $str == $xml ) {
                    $label = '<p>播出编单:'.Channel::GROUPS[$ch->name].' 日期:'.$ch->air_date.' 文件:'.$ch->name.'_'.$ch->air_date.'.xml 检查结果：<span class="label label-success">通过</span></p>';
                }
                else {
                    $label = '<p>播出编单:'.Channel::GROUPS[$ch->name].' 日期:'.$ch->air_date.' 文件:'.$ch->name.'_'.$ch->air_date.'.xml 检查结果：<span class="label label-danger">不通过</span></p>';
                    $data = '<tr><td>播放编单数据和格非串联单xml文件存在差异，请重新“加锁”';
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
    
    
    public function render2($key = null)
    {
        $ch = Channel::find($key);
        $label = '';
        if(!$ch) $data = '<tr><td>播出编单不存在</td></tr>';
        else {
            if(Storage::disk('xml')->exists($ch->name.'_'.$ch->air_date.'.xml')) 
            {
                $file = Storage::disk('xml')->get($ch->name.'_'.$ch->air_date.'.xml');
                
                $items = XmlReader::parseXml($file);
                $error = false;
                // $list = DB::table('material')->whereIn('unique_no', array_unique($items))
                //             ->where('status', '<>', Material::STATUS_READY)->select(['name','unique_no','duration'])
                //             ->get();
                $data = '<tr><th>'.__('Name').'</th><th>'.__('Unique no').'</th><th>'.__('Duration').'</th><th></th></tr>';
                if(count($items)) {
                    
                    foreach(array_unique($items) as $item)
                    {
                        $m = Material::where('unique_no', $item)->select(['name','unique_no','duration','status'])->first();
                        if(!$m) {
                            $data .= '<tr><td> </td><td>'.$item.'</td><td> </td><td>物料不存在</td></tr>';
                            $error = true;
                        }
                        else {
                            if($m->status != Material::STATUS_READY) {
                                $error = true;
                                $data .= '<tr><td>'.$m->name.'</td><td>'.$m->unique_no.'</td><td>'.$m->duration.'</td><td>物料缺失</td></tr>';
                            }
                        }
                    }
                }
                else {
                    $error = true;
                    
                }

                if($error) $label = '<p>播出编单:'.Channel::GROUPS[$ch->name].' 日期:'.$ch->air_date.' 文件:'.$ch->name.'_'.$ch->air_date.'.xml 检查结果：<span class="label label-danger">不通过</span></p>';
                else $label = '<p>播出编单:'.Channel::GROUPS[$ch->name].' 日期:'.$ch->air_date.' 文件:'.$ch->name.'_'.$ch->air_date.'.xml 检查结果：<span class="label label-success">通过</span></p>';
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