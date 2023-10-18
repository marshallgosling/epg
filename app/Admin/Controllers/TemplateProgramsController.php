<?php

namespace App\Admin\Controllers;

use App\Models\TemplatePrograms;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TemplateProgramsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'TemplatePrograms';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TemplatePrograms());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('unique_id', __('Unique id'));
        $grid->column('template_id', __('Template id'));
        $grid->column('order_no', __('Order no'));
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
        $show = new Show(TemplatePrograms::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('unique_id', __('Unique id'));
        $show->field('template_id', __('Template id'));
        $show->field('order_no', __('Order no'));
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
        $form = new Form(new TemplatePrograms());

        $form->text('name', __('Name'));
        $form->text('unique_id', __('Unique id'));
        $form->number('template_id', __('Template id'));
        $form->number('order_no', __('Order no'));

        return $form;
    }
}
