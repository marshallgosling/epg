<?php

namespace App\Admin\Controllers;

use App\Models\BlackList;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BlackListController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '黑名单';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BlackList());

        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('keyword', __('Keyword'))->sortable();
        $grid->column('group', __('Group'))->hide();
        $grid->column('status', __('Status'))->using(BlackList::STATUS)->label(['warning','danger','success','default']);
        $grid->column('scaned_at', __('Scaned at'))->sortable();
        $grid->column('data', __('Data'))->display(function () {
            return '展开';
        })->expand(function ($model) {
            return new Box('Json', view('admin.form.config', ['id'=>$model->id,'config'=>json_encode($model->data, JSON_UNESCAPED_UNICODE)]));
        });
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->like('keyword', __('Keyword'));
            $filter->in('status', __('Status'))->checkbox(BlackList::STATUS);
            
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
        $show = new Show(BlackList::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('keyword', __('Keyword'));
        $show->field('group', __('Group'));
        $show->field('status', __('Status'))->using(BlackList::STATUS);
        $show->field('scaned_at', __('Scaned at'));
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
        $form = new Form(new BlackList());

        $form->text('keyword', __('Keyword'));
        $form->text('group', __('Group'));

        return $form;
    }
}
