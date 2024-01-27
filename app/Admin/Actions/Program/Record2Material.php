<?php
namespace App\Admin\Actions\Material;

use App\Models\Material;
use App\Models\Record2 as Record;
use Illuminate\Contracts\Support\Renderable;

class Record2Material implements Renderable
{
    public function render($key = null)
    {
        $data = '<tr><td><h4>没有匹配到素材信息</h4></td></tr>';
        $m = Material::where('unique_no', Record::find($key)->unique_no)->first();
        if($m) {
        
            $data = '<tr><td><b>'.__('Name').'</b></td><td colspan="3">'.$m->name.'</td></tr>';
            $data .= '<tr><td><b>'.__('Unique no').'</b></td><td>'.$m->unique_no.'</td><td><b>'.__('Category').'</b></td><td>'.$m->category.'</td></tr>';
            $data .= '<tr><td><b>'.__('Filepath').'</b></td><td colspan="3">'.$m->filepath.'</td></tr>';
            $data .= '<tr><td><b>'.__('Duration').'</b></td><td>'.$m->duration.'</td><td><b>'.__('Frames').'</b></td><td>'.$m->frames.'</td></tr>';
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