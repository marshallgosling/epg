<?php
namespace App\Admin\Actions\Material;

use App\Models\Material;
use Encore\Admin\Widgets\Box;
use Illuminate\Contracts\Support\Renderable;

class ShowMaterial implements Renderable
{
    public function render($key = null)
    {
        $data = '';
        $m = Material::find($key);
        if($m) {
        
            $data .= '<tr><td><b>'.__('Name').'</b></td><td colspan="3">'.$m->name.'</td></tr>';
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
        $box = new Box('素材信息', $html);
        $box->style('info');
        $box->solid();
        return $box->render();
    }
}