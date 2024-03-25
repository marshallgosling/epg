<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \App\Models\Statistic;

class StatisticController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '统计数据';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Statistic());

        $grid->column('id', __('Id'));
        $grid->column('model', __('Model'));
        $grid->column('column', __('Column'));
        $grid->column('type', __('Type'))->using(Statistic::TYPES)->label();
        $grid->column('date', __('Air date'))->display(function ($date) {
            return "<small>$date</small>";
        });
        $grid->column('value', __('Value'))->sortable();
        $grid->column('category', __('Category'))->label('info');
        $grid->column('comment', __('Comment'));
        
        $grid->column('group', __('Group'))->hide();
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->filter(function(Grid\Filter $filter){
            $filter->column(6, function (Grid\Filter $filter) {
                //$filter->('column', __('Column'));
                $filter->mlike('comment', __('Comment'))->placeholder('输入%作为通配符，如 灿星% 或 %灿星%');
                $filter->equal('date', __('Air date'))->date('Y-m-d');
                $filter->in('category', __('Category'))->multipleSelect(Category::getFormattedCategories());
            });

            $filter->column(6, function (Grid\Filter $filter) {
                $filter->in('model', __('Model'))->checkbox(Statistic::MODELS);
                $filter->equal('group', __('Group'))->radio(Statistic::GROUPS);
                $filter->equal('type',  __('Type'))->radio(Statistic::TYPES);
                
            });
        });

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            
            $actions->disableEdit();
            $actions->disableDelete();
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->disableBatchActions();
        });

        $grid->disableCreateButton();

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
        $show = new Show(Statistic::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('model', __('Model'));
        $show->field('column', __('Column'));
        $show->field('value', __('Value'));
        $show->field('type', __('Type'))->using(Statistic::TYPES);
        $show->field('group', __('Group'))->using(Statistic::GROUPS);
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
        $form = new Form(new Statistic());

        $form->radio('model', __('Model'));
        $form->text('column', __('Column'));
        $form->number('value', __('Value'));
        $form->switch('type', __('Type'))->options(Statistic::TYPES);
        $form->text('group', __('Group'))->options(Statistic::GROUPS);

        return $form;
    }
}
