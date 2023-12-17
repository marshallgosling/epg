<?php

namespace App\Admin\Controllers;

use App\Models\Channel;
use App\Models\Epg;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class EpgController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Epg';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Epg());

        $grid->column('id', __('Id'))->hide();
        
        $grid->column('group_id', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        
        $grid->column('start_at', __('Start at'));
        $grid->column('end_at', __('End at'))->display(function ($end_at) {
            return substr($end_at, 11);
        });
        $grid->column('duration', __('Duration'));

        $grid->column('name', __('Name'));

        $grid->column('unique_no', __('Unique no'));
        $grid->column('category', __('Category'));
        //$grid->column('program_id', __('Program id'));
        $grid->column('comment', __('Comment'));

        $grid->disableCreateButton();
        $grid->disableBatchActions();
        $grid->disableActions();

        $grid->filter(function(Grid\Filter $filter){
            $filter->column(8, function (Grid\Filter $filter) {
                $filter->equal('group_id', __('Group'))->radio(Channel::GROUPS);
                $filter->between('start_at', __('Air date'))->datetime();
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
        $show = new Show(Epg::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('channel_id', __('Channel id'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        $show->field('duration', __('Duration'));
        $show->field('unique_no', __('Unique no'));
        $show->field('category', __('Category'));
        $show->field('program_id', __('Program id'));
        $show->field('comment', __('Comment'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Epg());

        $form->text('name', __('Name'));
        $form->text('channel_id', __('Channel id'));
        $form->datetime('start_at', __('Start at'))->default(date('Y-m-d H:i:s'));
        $form->datetime('end_at', __('End at'))->default(date('Y-m-d H:i:s'));
        $form->text('duration', __('Duration'));
        $form->text('unique_no', __('Unique no'));
        $form->text('category', __('Category'));
        $form->text('program_id', __('Program id'));
        $form->text('comment', __('Comment'));

        return $form;
    }
}
