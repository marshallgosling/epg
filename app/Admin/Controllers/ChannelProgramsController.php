<?php

namespace App\Admin\Controllers;

use App\Models\Channel;
use App\Models\ChannelPrograms;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ChannelProgramsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Channel【V】编单';

    protected $description = [
        'index'  => "每日节目编单具体编排数据，可以编辑及排序",
//        'show'   => 'Show',
//        'edit'   => 'Edit',
//        'create' => 'Create',
];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ChannelPrograms());


        //$grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('schedule_start_at', __('Schedule start at'));
        $grid->column('schedule_end_at', __('Schedule end at'));
        $grid->column('start_at', __('Start at'));
        $grid->column('end_at', __('End at'));
        $grid->column('duration', __('Duration'));
        $grid->column('version', __('Version'));
        //$grid->column('channel_id', __('Channel id'));
        //$grid->column('data', __('Data'));
        //$grid->column('created_at', __('Created at'));
        //$grid->column('updated_at', __('Updated at'));

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->equal('channel_id', __('Air date'))->select(Channel::orderBy('id', 'desc')->limit(20)->get()->pluck('air_date', 'id'));
            
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
        $show = new Show(ChannelPrograms::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('schedule_start_at', __('Schedule start at'));
        $show->field('schedule_end_at', __('Schedule end at'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        $show->field('duration', __('Duration'));
        $show->field('version', __('Version'));
        $show->field('channel_id', __('Channel id'));
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
        $form = new Form(new ChannelPrograms());

        $form->text('name', __('Name'));
        $form->text('schedule_start_at', __('Schedule start at'));
        $form->text('schedule_end_at', __('Schedule end at'));
        $form->datetime('start_at', __('Start at'))->default(date('Y-m-d H:i:s'));
        $form->datetime('end_at', __('End at'))->default(date('Y-m-d H:i:s'));
        $form->text('duration', __('Duration'));
        $form->number('version', __('Version'));
        $form->number('channel_id', __('Channel id'));
        $form->text('data', __('Data'));

        return $form;
    }
}
