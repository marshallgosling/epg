<?php
namespace App\Admin\Actions\Material;

use App\Jobs\Material\MediaInfoJob;
use App\Models\Material;
use App\Tools\Material\MediaInfo;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Artisan;

class CheckMediaInfo implements Renderable
{
    public function render($key = null)
    {
        $m = Material::find($key);
        
        $data = '<tr><td><h4>没有素材 mxf 格式信息</h4></td></tr>';
        if($m) {
            MediaInfoJob::dispatch($key, 'view');
            $unique_no = $m->unique_no;
            $data = '<tr><td><b>'.__('Unique no').'</b></td><td>'.$m->unique_no.'</td><td><b>'.__('Category').'</b></td><td>'.$m->category.'</td></tr>';
            $data .= '<tr><td><b>'.__('Filepath').'</b></td><td colspan="3">'.$m->filepath.'</td></tr>';
            $data .= '<tr><td><b>MediaInfo</b></td><td id="code" colspan="3" style="height:400px;overflow:scroll;">loading...</td></tr>';
            
        }

        $html = <<<HTML
        <div class="table-responsive">
            <table class="table table-striped">
                {$data}
            </table>
        </div>
        <script>
            function getCode()
            {
                var code = "{$unique_no}";
                $.ajax({
                    method: 'get',
                    url: '/admin/api/mediainfo',
                    data: {unique_no: code},
                    success: function (data) {
                        $('#code').html('<code>'+data+'</code>');
                    }
                });
            }
            setTimeout("getCode()", 1500);
        </script>
HTML;
        
        return $html;
    }
}