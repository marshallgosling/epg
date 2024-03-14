<?php

namespace App\Admin\Controllers;

use App\Models\Agreement;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AgreementController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '剧集有效期合同';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Agreement());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('start_at', __('Start at'));
        $grid->column('end_at', __('End at'));
        $grid->column('status', __('Status'));
        $grid->column('comment', __('Comment'));
        $grid->column('created_at', __('Created at'))->hide();
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
        $show = new Show(Agreement::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        $show->field('status', __('Status'));
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
        $form = new Form(new Agreement());

        $form->text('name', __('Name'));
        $form->date('start_at', __('Start at'))->default(date('Y-m-d'));
        $form->date('end_at', __('End at'))->default(date('Y-m-d'));
        $form->switch('status', __('Status'));
        $form->text('comment', __('Comment'));

        return $form;
    }
}
