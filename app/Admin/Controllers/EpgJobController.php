<?php

namespace App\Admin\Controllers;

use App\Models\EpgJob;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class EpgJobController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'EpgJob';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new EpgJob());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('group_id', __('Group id'));
        $grid->column('status', __('Status'));
        $grid->column('file', __('File'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(EpgJob::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('group_id', __('Group id'));
        $show->field('status', __('Status'));
        $show->field('file', __('File'));
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
        $form = new Form(new EpgJob());

        $form->text('name', __('Name'));
        $form->text('group_id', __('Group id'));
        $form->switch('status', __('Status'));
        $form->file('file', __('File'));

        return $form;
    }
}
