<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\BlackList\Apply;
use App\Admin\Actions\BlackList\Scanner;
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
        $grid->column('group', __('Group'))->using(BlackList::GROUPS);
        $grid->column('status', __('Status'))->using(BlackList::STATUS)->label(['warning','danger','success','default']);
        $grid->column('scaned_at', __('Scaned at'))->sortable();
        $grid->column('data', __('Data'))->display(function () {
            return '展开';
        })->expand(function ($model) {
            return new Box('Json', view('admin.form.config', ['id'=>$model->id,'config'=>$model->data]));
        });
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->filter(function($filter){

            $filter->like('keyword', __('Keyword'));
            $filter->in('status', __('Status'))->checkbox(BlackList::STATUS);
            
        });

        $grid->actions(function ($actions) {
            $actions->add(new Scanner);
            $actions->add(new Apply);
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
        $show->field('group', __('Group'))->using(BlackList::GROUPS);
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
        $form->radio('group', __('Group'))->options(BlackList::GROUPS);

        return $form;
    }
}
