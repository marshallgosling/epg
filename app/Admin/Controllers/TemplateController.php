<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Template\Programs;
use App\Models\Template;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Widgets\InfoBox;

class TemplateController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '模版';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Template());

        $grid->model()->where('group_id', 'default')->with('programs')->orderBy('sort', 'asc');
        //$grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->expand(function ($model) {
            $programs = $model->programs()->take(10)->get()->map(function ($program) {  
                return $program->only(['id', 'name', 'category', 'order_no', 'created_at']);
            });

            $items = $programs->toArray();
            
            if(count($items) == 0) $info = "没有模版条目记录，请点击添加";
            else $info = '当前最多只显示10条记录，请点击查看';

            $infoBox = '<div class="small-box bg-aqua" style="margin-bottom:0"><a href="channelv/tree/'.$this->id.'" class="small-box-footer">'.$info.'<i class="fa fa-arrow-circle-right"></i></a></div>';
            
            return $infoBox.(new Table(['ID', '名称', '栏目', '排序', '创建时间'], $items))->render();
        });
        $grid->column('version', __('Version'))->display(function ($version) {
            return '<span class="label label-default">'.$version.'</span>';
        });
        $grid->column('start_at', __('Start at'))->display(function($start_at) {
            $today = strtotime(date('Y-m-d 6:00:00'));
            $air = strtotime(date('Y-m-d '.$start_at));
            $html = $start_at;
            if( $air < $today ) $html .= ' <span class="label label-default">次日</span>';
            return $html;
        });
        $grid->column('duration', __('Duration'));
        $grid->column('schedule', __('Schedule'))->using(Template::SCHEDULES);;
        $grid->column('sort', __('Sort'));
        $grid->column('updated_at', __('Updated at'));

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->like('name', __('Name'));
            $filter->equal('schedule', __('Schedule'))->radio(Template::SCHEDULES);
        
        });

        $grid->actions(function ($actions) {

            // append一个操作
            $actions->add(new Programs);
        
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
        $show = new Show(Template::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('schedule', __('Schedule'))->using(Template::SCHEDULES);
        $show->field('start_at', __('Start at'));
        $show->field('duration', __('Duration'));
        $show->field('version', __('Version'));
        $show->field('sort', __('Sort'));
        $show->field('comment', __('Comment'));
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
        $form = new Form(new Template());

        $form->text('version', __('Version'))->disable();
        $form->text('name', __('Name'));
        $form->radio('schedule', __('Schedule'))->options(Template::SCHEDULES);
        
        $form->text('start_at', __('Start at'));
        $form->text('duration', __('Duration'));

        $form->text('sort', __('Sort'));
        $form->text('comment', __('Comment'));


        return $form;
    }
}
