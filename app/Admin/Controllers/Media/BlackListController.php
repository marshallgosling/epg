<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\BlackList\Apply;
use App\Admin\Actions\BlackList\Scanner;
use App\Models\BlackList;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class BlackListController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '黑名单';

    public function results($id, Content $content)
    {
        $title = '黑名单扫描结果';
        $description = '';
       
        return $content
            ->title($title)
            ->description($description ?? trans('admin.list'))
            ->body(view('admin.black', ['content'=>$this->table($id)]));
    }

    private function table($id)
    {
        $black = BlackList::find($id);
        $rows = [];
        if($black) {
            $list = json_decode($black->data);
            
            $available = 0;
            if(is_array($list))foreach($list as $item) {
                $programs = $item->programs;
                if(is_array($programs))foreach($programs as $pro)
                {
                    $idx = $item->id;
                    $rows[] = [
                        '<input type="checkbox" class="grid-row-checkbox" data-id="'.$idx.'" autocomplete="off">', 
                        date('Y-m-d H:i:s', strtotime($pro->start_at)), $pro->name.'( '.$pro->unique_no.' ) '.$pro->artist,
                        $pro->duration, $item->name, '<a class="btn btn-sm btn-primary" href="javascript:showSearchModel('.$idx.');">选择</a>', $pro->category
                    ];
                    $available ++;
                }
            }
        }

        $head = ["", "日期时间", "编单内容", "时长", "节目名", "替换操作", "栏目"];
        $html = (new Table($head, $rows, ['table-hover', 'grid-table']))->render();
        //$html .= '<p><form action="/admin/media/recognize" method="post" class="form-horizontal" accept-charset="UTF-8" pjax-container=""><p><button type="submit" class="btn btn-primary">提 交</button></p></form>';

        $js = <<<JS
        function showSearchModal(idx) {
        if(sortEnabled) {
            toastr.error("请先保存排序结果。");
            return;
        }
        selectedIndex = idx;

        $('#searchModal').modal('show');
        $('#confirmBtn').removeAttr('disabled');
    }
JS;
        return new Box('扫描结果，总共 '.$available.' 个匹配项', $html);

    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BlackList());

        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('keyword', __('Keyword'))->sortable();
        $grid->column('group', __('Group'))->using(BlackList::GROUPS);
        $grid->column('status', __('Status'))->using(BlackList::STATUS)->label(['warning','danger','success','default']);
        $grid->column('scaned_at', __('Scaned at'))->sortable();
        $grid->column('list', __(' '))->display(function () {
            return '<a href="./blacklist/result/'.$this->id.'">处理扫描</a>';
        });
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->filter(function(Grid\Filter $filter){

            $filter->like('keyword', __('Keyword'));
            $filter->in('status', __('Status'))->checkbox(BlackList::STATUS);
            
        });

        $grid->actions(function ($actions) {
            $actions->add(new Scanner);
            $actions->add(new Apply);
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(BlackList::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('keyword', __('Keyword'));
        $show->field('group', __('Group'))->using(BlackList::GROUPS);
        $show->field('status', __('Status'))->using(BlackList::STATUS);
        $show->field('scaned_at', __('Scaned at'));
        $show->field('data', __('Data'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BlackList());

        $form->text('keyword', __('Keyword'));
        $form->radio('group', __('Group'))->options(BlackList::GROUPS);

        return $form;
    }
}
