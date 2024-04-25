<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \App\Models\Keywords;
use Illuminate\Support\MessageBag;

class KeywordsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '翻译对照表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Keywords());
        $grid->model()->orderBy('id', 'desc');

        //$grid->column('id', __('Id'));
        
        $grid->column('category', __('Category'))->using(Keywords::CATES);
        $grid->column('keyword', __('Keyword'));
        $grid->column('status', __('Status'))->hide();
        $grid->column('value', __('翻译'));
        $grid->column('language', __('Language'));
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->column(6, function(Grid\Filter $filter) {
                $filter->like('keyword', __('Keyword'));
                $filter->equal('category', __('Category'))->select(Category::getXkcCategories());
            });
            $filter->column(6, function(Grid\Filter $filter) {
                $filter->like('value', '翻译');
            });
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
        $show = new Show(Keywords::findOrFail($id));

        $show->field('id', __('Id'));

        $show->field('category', __('Category'))->using(Keywords::CATES);
        $show->field('keyword', __('Keyword'));
        
        $show->field('status', __('Status'));
        $show->field('value', __('Value'));
        $show->field('language', __('Language'));
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
        $form = new Form(new Keywords());

        // $form->select('keyword', __('Keyword'))->options(function ($id) {
        //     return [$id => $id];
        // })->ajax('/admin/api/episode')->required();

        $form->select('category', __('Category'))->options(Keywords::CATES)->required();
        $form->text('keyword', __('Keyword'))->required();
        //$form->switch('status', __('Status'));
        $form->text('value', __('Value'))->required();
        $form->radio('language', __('Language'))->options(['en'=>'en-us'])->default('en');

        if($form->isCreating()) {
            $error = new MessageBag([
                'title'   => '创建失败',
                'message' => '该剧集名称 '. $form->name.' 已存在。',
            ]);

            if(Keywords::where('keyword', $form->name)->exists())
            {
                return back()->with(compact('error'));
            }
        }

        if($form->isEditing()) {
            $error = new MessageBag([
                'title'   => '修改失败',
                'message' => '该剧集名称 '. $form->name.' 已存在。',
            ]);

            if(Keywords::where('keyword', $form->name)->where('id','<>',$form->model()->id)->exists())
            {
                return back()->with(compact('error'));
            }
        }


        return $form;
    }
}
