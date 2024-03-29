<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Extensions\MyTable;
use App\Http\Controllers\Controller;
use App\Jobs\Material\ScanFolderJob;
use App\Models\Folder;
use Encore\Admin\Layout\Content;
use Illuminate\Support\MessageBag;
use App\Models\Material;
use App\Tools\Material\RecognizeFileInfo;
use Encore\Admin\Widgets\Box;

class ProcessMaterialController extends Controller
{
    public function index($id, Content $content)
    {
        $title = '播出池物料对比结果';
        $description = '这里列出的是存在物料文件，但是没有对应的物料记录的文件对比数据';
        
        return $content
            ->title($title)
            ->description($description ?? trans('admin.list'))
            ->body($this->folder($id));
    }

    protected function folder($id, $process=false)
    {
        $folder = Folder::find($id);
        $head = ["", "文件名", "扫描结果", "分析结果", "操作"];
        $list = json_decode($folder->data);
        $rows = [];
        $available = 0;
        if(is_array($list))foreach($list as $idx=>$item) {
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
                $result = '<i class="fa fa-close text-red"></i>';
                $material = '';

                if($item->name == '' || $item->unique_no == '') {
                    if($item->name) {
                        $material = "可新建物料 (播出编号:<span class=\"label label-warning\">自动生成</span>, 节目名:<span class=\"label label-default\">{$item->name}</span>)";
                        $result = '<i class="fa fa-check text-green"></i>';
                        $available ++;
                    }
                    if($item->unique_no) {
                        $material = "不可新建物料（播出编号:<span class=\"label label-default\">{$item->unique_no}</span> <span class=\"label label-danger\">缺少节目标题</span>)";
                    }
                }
                else {
                    $result = '<i class="fa fa-check text-green"></i>';
                    $material = "可新建物料 (播出编号:<span class=\"label label-default\">{$item->unique_no}</span> 节目名:<span class=\"label label-default\">{$item->name}</span>";
                    $available ++;
                }
                
            }
            $rows[] = ['style'=>'','item'=>[
                '<input type="checkbox" class="grid-row-checkbox" data-id="'.$idx.'" autocomplete="off">', 
                $item->filename, $result, $material, ''
            ]];
        }

        $html = (new MyTable($head, $rows, ['table-hover', 'grid-table']))->render();
        if($process) $html .= '<p><form action="/admin/media/recognize" method="post" class="form-horizontal" accept-charset="UTF-8" pjax-container=""><p><button type="submit" class="btn btn-primary">提 交</button></p></form>';

        return new Box('目标路径文件夹 '.$folder->path. ' 扫描结果，总共 '.$available.' 个可用文件, '.(count($rows)-$available).' 个不可用文件', $html);

    }

    public function local(Content $content)
    {
        $title = '手动批处理物料入库';
        $description = '';
        
        return $content
            ->title($title)
            ->description($description ?? trans('admin.list'))
            ->body($this->folder(8, true));
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

        return new Box('目标路径文件夹 '.config('CUSTOMER_MATERIAL_FOLDER').'，总共 '.count($rows).' 个文件', $html);

    }

    private function compare()
    {
        $d = dir(config('CUSTOMER_MATERIAL_FOLDER'));
        if(!$d) return false;

        $list = [];
        while (($file = $d->read()) !== false){
            if($file != '.' && $file != '..')
            {
                $f = RecognizeFileInfo::recognizeAll($file);
                if($f) $list[] = $f;
            }
        }
        $d->close();
        return $list;
    }

    public function process()
    {
        $id = 8;
        ScanFolderJob::dispatch($id, 'apply')->queue('media');
        $error = new MessageBag([
            'title'   => '处理任务发起成功',
            'message' => '请耐心等待处理结果'
        ]);
        return back()->with(compact('error'));
    } 
}