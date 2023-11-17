<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Template\BatchReplicate;
use App\Admin\Actions\Template\Replicate;
use App\Models\Category;
use App\Models\Template;
use App\Models\TemplatePrograms;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class TemplateProgramsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '模版节目编排';

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
        $grid->column('category', __('Category'))->display(function($category) {
            $category = array_map(function ($c) {
                return "<span class='label label-info'>{$c}</span>";
            }, $category);
            return implode(' ', $category);
        });
        $grid->column('type', __('Type'))->using(TemplatePrograms::TYPES, 0);
        $grid->column('sort', __('Sort'));
        //$grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->equal('template_id', __('Template'))->select(Template::selectRaw("concat(start_at, ' ', name) as name, id")->get()->pluck('name', 'id'));
            
        });

        $grid->actions(function ($actions) {
            $actions->add(new Replicate);
        });

        $grid->batchActions(function ($actions) {
            $actions->add(new BatchReplicate);
        });

        $grid->paginate(100);

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
        $show->field('category', __('Category'));
        $show->field('type', __('Type'))->using(TemplatePrograms::TYPES, 0);
        $show->field('sort', __('Sort'));
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
        $form->multipleSelect('category', __('Category'))->options(Category::getFormattedCategories());
        $form->select('template_id', __('Template'))->options(Template::selectRaw("concat(start_at, ' ', name) as name, id")->get()->pluck('name', 'id'));
        $form->radio('type', __('Type'))->options(TemplatePrograms::TYPES);
        $form->number('sort', __('Sort'))->default(0);
        $form->json('data', __('Data'));

        $form->saved(function (Form $form) {
            $temp = Template::find($form->template_id);
            if($temp) {
                $temp->version = $temp->version + 1;
                $temp->save();
            }
        });

        return $form;
    }
}
