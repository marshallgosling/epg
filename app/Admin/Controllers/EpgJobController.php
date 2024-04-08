<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Template\Reverse;
use App\Models\Channel;
use App\Models\EpgJob;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class EpgJobController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '节目自动编单任务记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new EpgJob());
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('group_id', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        $grid->column('status', __('Status'))->using(EpgJob::STATUS);
        $grid->column('file', __('File'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->disableCreateButton();

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new Reverse);
        });
        $grid->disableActions();
        $grid->disableBatchActions();

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
        $show = new Show(EpgJob::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('group_id', __('Group'))->using(Channel::GROUPS);
        $show->field('status', __('Status'))->using(EpgJob::STATUS);
        $show->field('file', __('File'));
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
        $form = new Form(new EpgJob());

        $form->text('name', __('Name'));
        $form->radio('group_id', __('Group'))->options(Channel::GROUPS);
        $form->radio('status', __('Status'))->options(EpgJob::STATUS);
        $form->text('file', __('File'));

        return $form;
    }
}
