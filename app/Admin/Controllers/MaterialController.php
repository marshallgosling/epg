<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\Material;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MeterialController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '物料管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Material());

        $grid->model()->orderBy('id', 'desc');
        //$grid->column('id', __('Id'));
        $grid->column('unique_no', __('Unique_no'));
        $grid->column('name', __('Name'));  
        $grid->column('category', __('Category'))->display(function () {
            return Category::findCategory($this->category);
        });
        $grid->column('duration', __('Duration'));
        $grid->column('size', __('Size'));
        $grid->column('frames', __('Frames'));
        $grid->column('created_at', __('Created at'));
        //$grid->column('updated_at', __('Updated at'));

        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        $grid->actions(function ($actions) {
            $actions->disableView();
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
        $show = new Show(Material::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('unique_no', __('Unique no'));
        $show->field('category', __('Category'))->using(Category::getFormattedCategories());
        $show->field('duration', __('Duration'));
        $show->field('size', __('Size'));
        $show->field('frames', __('Frames'));
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
        $form = new Form(new Material());

        $form->text('name', __('Name'))->required();
        $form->text('unique_no', __('Unique no'))->required();
        $form->select('category', __('Category'))->options(Category::getFormattedCategories())->required();
        $form->text('duration', __('Duration'))->required();
        $form->text('frames', __('Frames'))->required();
        $form->text('size', __('Size'));
        

        return $form;
    }
}
