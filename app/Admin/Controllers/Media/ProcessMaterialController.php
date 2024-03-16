<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Extensions\MyTable;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use Encore\Admin\Layout\Content;

use App\Models\Material;
use App\Tools\Material\RecognizeFileInfo;
use Encore\Admin\Widgets\Box;

class ProcessMaterialController extends Controller
{
    public function index($id, Content $content)
    {
        $title = '手动批处理物料入库';
        $description = '';
        
        return $content
            ->title($title)
            ->description($description ?? trans('admin.list'))
            ->body($this->folder($id));
    }

    protected function folder($id)
    {
        $folder = Folder::find($id);
        $head = ["", "文件名", "扫描结果", "分析结果", "操作"];
        $list = json_decode($folder->data);
        $rows = [];
        if($list)foreach($list as $idx=>$item) {
            if($item->m){
                $m = $item->m;
                $result = '<i class="fa fa-check text-green"></i>';
                $s = $m->status == Material::STATUS_READY ? '<i class="fa fa-check text-green"></i>' : '<i class="fa fa-close text-red"></i>';
                $material = 
                    ' <span class="label label-default">播出编号</span> <small>'.$m->unique_no.'</small>'.
                    ' <span class="label label-default">状态</span> '. $s .
                    ' <span class="label label-default">时长</span> <small>'.$m->duration.'</small>';
            }
            else {
                $result = '<i class="fa fa-close text-red" title="不匹配"></i>';
                $material = '';

                if($item->name == '' || $item->unique_no == '') {
                    if($item->name) {
                        $material = "可新建物料 (播出编号:<span class=\"label label-warning\">自动生成</span>, 节目名:<span class=\"label label-default\">{$item->name}</span>)";
                        $result = '<i class="fa fa-check text-green"></i>';
                    }
                    if($item->unique_no) {
                        $material = "不可新建物料（播出编号:<span class=\"label label-default\">{$item->unique_no}</span> <span class=\"label label-danger\">缺少节目标题</span>)";
                    }
                }
                else {
                    $result = '<i class="fa fa-check text-green"></i>';
                    $material = "可新建物料 (播出编号:<span class=\"label label-default\">{$item->unique_no}</span> 节目名:<span class=\"label label-default\">{$item->name}</span>";
                }
                
            }
            $rows[] = ['style'=>'','item'=>[
                '<input type="checkbox" class="grid-row-checkbox" data-id="'.$idx.'" autocomplete="off">', 
                $item->filename, $result, $material, ''
            ]];
        }

        $html = (new MyTable($head, $rows, ['table-hover', 'grid-table']))->render();
        //$html .= '<p><form action="/admin/media/recognize" method="post" class="form-horizontal" accept-charset="UTF-8" pjax-container=""><p><button type="submit" class="btn btn-primary">提 交</button></p></form>';

        return new Box('目标路径文件夹 '.$folder->path. ' 扫描结果', $html);

    }

    public function local(Content $content)
    {
        $title = '手动批处理物料入库';
        $description = '';
        
        return $content
            ->title($title)
            ->description($description ?? trans('admin.list'))
            ->body($this->grid());
    }

    /**
     * Make a grid builder.
     *
     * @return MyTable
     */
    protected function grid()
    {
        $head = ["", "文件名", "扫描结果", "物料", "操作"];
        $list = $this->compare();
        $rows = [];
        if($list)foreach($list as $idx=>$item) {
            if($item['m']){
                $m = $item['m'];
                $result = '<i class="fa fa-check text-green"></i>';
                $s = $m->status == Material::STATUS_READY ? '<i class="fa fa-check text-green"></i>' : '<i class="fa fa-close text-red"></i>';
                $material = 
                    ' <span class="label label-default">播出编号</span> <small>'.$m->unique_no.'</small>'.
                    ' <span class="label label-default">状态</span> '. $s .
                    ' <span class="label label-default">时长</span> <small>'.$m->duration.'</small>';
            }
            else {
                $result = '<i class="fa fa-close text-red" title="不匹配"></i>';
                $material =  '无';

                if($item['name'] == '' || $item['unique_no'] == '') {
                    if($item['name']) {
                        $material .= '，可新建物料（自动生成播出编号）';
                        $result = '<i class="fa fa-check text-green"></i>';
                    }
                    if($item['unique_no']) {
                        $material .= '，不可新建物料（缺少节目标题）';
                    }
                }
                else {
                    $result = '<i class="fa fa-check text-green"></i>';
                    $material .= '，可新建物料';
                }
                
            }
            $rows[] = ['style'=>'','item'=>[
                '<input type="checkbox" class="grid-row-checkbox" data-id="'.$idx.'" autocomplete="off">', 
                $item['filename'], $result, $material, ''
            ]];
        }

        $html = (new MyTable($head, $rows, ['table-hover', 'grid-table']))->render();
        $html .= '<p><form action="/admin/media/recognize" method="post" class="form-horizontal" accept-charset="UTF-8" pjax-container=""><p><button type="submit" class="btn btn-primary">提 交</button></p></form>';

        return new Box('目标路径文件夹 '.config('CUSTOMER_MATERIAL_FOLDER'), $html);

    }

    private function compare()
    {
        $d = dir(config('CUSTOMER_MATERIAL_FOLDER'));
        if(!$d) return false;

        $list = [];
        while (($file = $d->read()) !== false){
            if($file != '.' && $file != '..')
                $list[] = RecognizeFileInfo::recognize($file);
        }
        $d->close();
        return $list;
    }

    public function process()
    {
        return response()->json();
    } 
}