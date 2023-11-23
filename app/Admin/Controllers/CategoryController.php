<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Category\TestModal;
use App\Models\Category;
use App\Models\TemplatePrograms;
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
        $grid->column('duration', __('Duration'))->using(TemplatePrograms::TYPES);

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->like('name', __('Name'));
            $filter->like('no', __('CategoryNo'));
            $filter->equal('type', __('CategoryType'))->select(Category::TYPES);
            $filter->in('duration', __('Duration'))->checkbox(TemplatePrograms::TYPES);
        
        });

        /*$grid->rows(function (Grid\Tools $tools) {
            $tools->append(new TestModal());
        });

        $grid->actions(function ($actions) {
            $actions->add(new TestModal);
        });*/

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
        $show->field('duration', __('Duration'))->using(TemplatePrograms::TYPES);

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
        
        $form->radio('duration', __('Duration'))->options(TemplatePrograms::TYPES)->default('0');

        return $form;
    }
}
