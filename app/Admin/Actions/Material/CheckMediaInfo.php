<?php
namespace App\Admin\Actions\Material;

use App\Jobs\Material\MediaInfoJob;
use App\Models\Material;
use App\Tools\Material\MediaInfo;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CheckMediaInfo implements Renderable
{
    public function render($key = null)
    {
        $m = Material::find($key);
        $js = '';
        $data = '<tr><td><h4>没有素材 mxf 格式信息</h4></td></tr>';
        if($m) {
            $unique_no = $m->unique_no;

            $info = Cache::get('mediainfo_'.$unique_no);

            $data = '<tr><td><b>'.__('Unique no').'</b></td><td>'.$m->unique_no.'</td><td><b>'.__('Category').'</b></td><td>'.$m->category.'</td></tr>';
            $data .= '<tr><td><b>'.__('Filepath').'</b></td><td colspan="3">'.$m->filepath.'</td></tr>';
            if(!$m->filepath) {
                $data .= '<tr><td><b>MediaInfo</b></td><td colspan="3" style="height:400px;"><h4>没有素材 mxf 格式信息</h4></td></tr>';
           
            }
            else {

                if($info) {
                    $data .= '<tr><td><b>MediaInfo</b></td><td colspan="3" style="height:400px;"><textarea cols="120" rows="30">'.$info.'</textarea></td></tr>';
                }
                else {
                    MediaInfoJob::dispatch($key, 'view')->onQueue('media');
                    $data .= '<tr><td><b>MediaInfo</b></td><td id="mediainfo" colspan="3" style="height:400px;">loading...</td></tr>';
                
                    $js = <<<JS
                    <script>
                    function getCode()
                    {
                        let code = "{$unique_no}";
                        $.ajax({
                            method: 'get',
                            url: '/admin/api/mediainfo',
                            data: {unique_no: code},
                            success: function (data) {
                                $('#mediainfo').html('<textarea cols="120" rows="30">'+data+'</textarea>');
                            }
                        });
                    }
                    setTimeout("getCode()", 3000);
                </script>
                JS;
                }
            }
            
        }

        $html = <<<HTML
        <div class="table-responsive">
            <table class="table table-striped">
                {$data}
            </table>
        </div>
        {$js}
HTML;
        
        return $html;
    }
}