<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Template\Programs;
use App\Models\Template;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

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

        $grid->model()->where('group_id', 'default')->with('programs');
        //$grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->expand(function ($model) {
            $programs = $model->programs()->take(10)->get()->map(function ($program) {  
                return $program->only(['id', 'name', 'category', 'order_no', 'created_at']);
            });

            $items = $programs->toArray();
            foreach($items as &$item) {
                $category = array_map(function ($c) {
                    return "<span class='label label-info'>{$c}</span>";
                }, $item['category']);
                $item['category'] = implode(' ', $category);
            }
            
            return new Table(['ID', '名称', '栏目', '排序', '创建时间'], $items);
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
        $show->field('type', __('Type'));
        $show->field('status', __('Status'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        $show->field('summary', __('Summary'));
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

        $form->text('name', __('Name'));
        $form->number('type', __('Type'));
        $form->switch('status', __('Status'));
        $form->text('start_at', __('Start at'));
        $form->text('end_at', __('End at'));
        $form->text('summary', __('Summary'));

        return $form;
    }
}
