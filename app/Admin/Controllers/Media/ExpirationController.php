<?php

namespace App\Admin\Controllers\Media;

use App\Models\Channel;
use App\Models\Expiration;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;

class ExpirationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '剧集有效期管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Expiration());

        $grid->column('id', __('Id'));
        //$grid->column('group_id', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        $grid->column('status', __('Status'))->bool();

        $grid->column('name', __('Name'));
        $grid->column('start_at', __('Start at'));
        $grid->column('end_at', __('End at'));
        
        $grid->column('comment', __('Comment'))->hide();
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(Expiration::findOrFail($id));

        $show->field('id', __('Id'));
        //$show->field('group_id', __('Group id'))->using(Channel::GROUPS);
        $show->field('name', __('Name'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        $show->field('status', __('Status'))->using(Expiration::STATUS);
        $show->field('comment', __('Comment'));
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
        $form = new Form(new Expiration());

        //$form->radio('group_id', __('Group'))->options(Channel::GROUPS)->required();
        $form->hidden('group_id', '')->default('xkc');
        $form->select('name', __('Episodes'))->options(function ($id) {
            return [$id => $id];
        })->ajax('/admin/api/episode')->required();
        $form->date('start_at', __('Start at'))->default(date('Y-m-d'))->required();
        $form->date('end_at', __('End at'))->default(date('Y-m-d'))->required();
        $form->switch('status', __('Status'))->options(Expiration::STATUS);
        $form->textarea('comment', __('Comment'));

        $form->saving(function(Form $form) {

            if($form->isCreating()) {
                $error = new MessageBag([
                    'title'   => '创建失败',
                    'message' => '该剧集名称 '. $form->name.' 已存在。',
                ]);
    
                if(Expiration::where('name', $form->name)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改失败',
                    'message' => '该剧集名称 '. $form->name.' 已存在。',
                ]);
    
                if(Expiration::where('name', $form->name)->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            
        });

        return $form;
    }
}
