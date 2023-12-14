<?php

namespace App\Admin\Controllers;

use App\Models\Notification;
use App\Tools\Notify;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class NotificationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '通知列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Notification());

        //$grid->column('id', __('Id'));
        $grid->column('group_id', __('Group'))->using(Notification::GROUPS)->dot(['xkv'=>'info','xkc'=>'warning','xki' =>'success'], 'info');
        $grid->column('name', __('Name'));
        $grid->column('message', __('Message'))->style('max-width:200px;word-break:break-all;');
        $grid->column('type', __('Type'))->using(Notification::TYPES);
        $grid->column('level', __('Level'))->using(Notification::LEVELS);
        $grid->column('user', __('User'));
        $grid->column('viewed', __('Viewed'))->bool();
        
        $grid->column('created_at', __('Created at'))->filter('range', 'datetime');
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->disableCreateButton();

        $grid->filter(function(Grid\Filter $filter){
            $filter->column(4, function (Grid\Filter $filter) {
                $filter->equal('group_id', __('Group'))->radio(Notification::GROUPS);
                $filter->like('name', __('Name'));
                
            });
            $filter->column(8, function (Grid\Filter $filter) {
                $filter->equal('type', __('Type'))->radio(Notification::TYPES);
                $filter->equal('level', __('Level'))->radio(Notification::LEVELS);
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
        Notify::setViewed($id);

        $show = new Show(Notification::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('group_id', __('Group'))->using(Notification::GROUPS);
        $show->field('name', __('Name'));
        $show->field('message', __('Message'));
        $show->field('type', __('Type'))->using(Notification::TYPES);
        $show->field('level', __('Level'))->using(Notification::LEVELS);
        $show->field('user', __('User'));
        $show->field('viewed', __('Viewed'));
        
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
        $form = new Form(new Notification());

        $form->radio('group_id', __('Group'))->options(Notification::GROUPS);
        $form->text('name', __('Name'));
        $form->text('message', __('Message'));
        $form->radio('type', __('Type'))->options(Notification::TYPES);
        $form->radio('level', __('Level'))->options(Notification::LEVELS);
        $form->text('user', __('User'));
        $form->switch('viewed', __('Viewed'));
        

        return $form;
    }
}
