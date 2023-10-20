<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '分类管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Category());

        $grid->model()->orderBy('id', 'desc');
        //$grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->display(function () {
            return "<a href=\"category/{$this->id}/edit\">$this->name</a>";
        });
        $grid->column('no', __('CategoryNo'));
        $grid->column('type', __('CategoryType'))->using(Category::TYPES);
        $grid->column('duration', __('Duration'));

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->like('name', __('Name'));
            $filter->equal('unique_no', __('Unique_no'));
            $filter->equal('type', __('CategoryType'))->select(Category::TYPES);
        
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
        $show = new Show(Category::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('no', __('CategoryNo'));
        $show->field('type', __('CategoryType'))->using(Category::TYPES);
        $show->field('duration', __('Duration'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Category());

        $form->text('name', __('Name'))->rules('required');
        //$form->select('no', __('CategoryNo'))->options(Category::getFormattedCategories());
        $form->text('no', __('CategoryNo'))->rules('required|max:10');
        $form->select('type', __('CategoryType'))->options(Category::TYPES)->rules('required');
        $form->text('duration', __('Duration'));

        return $form;
    }
}
