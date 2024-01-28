<?php
namespace App\Admin\Actions\Material;

use App\Models\Material;
use App\Tools\Material\MediaInfo;
use Illuminate\Contracts\Support\Renderable;

class CheckMediaInfo implements Renderable
{
    public function render($key = null)
    {
        $m = Material::find($key);
        $data = '<tr><td><h4>没有素材 mxf 格式信息，文件：'.$m->filepath.'</h4></td></tr>';
        if($m && file_exists($m->filepath)) {
        
            $data = '<tr><td><b>'.__('Unique no').'</b></td><td>'.$m->unique_no.'</td><td><b>'.__('Category').'</b></td><td>'.$m->category.'</td></tr>';
            $data .= '<tr><td><b>'.__('Filepath').'</b></td><td colspan="3">'.$m->filepath.'</td></tr>';
            $data .= '<tr><td><b>MediaInfo</b></td><td colspan="3"><code>'.MediaInfo::geRawInfo($m).'</code></td></tr>';
        }

        $html = <<<HTML
        <div class="table-responsive">
            <table class="table table-striped">
                {$data}
            </table>
        </div>
HTML;
        
        return $html;
    }
}