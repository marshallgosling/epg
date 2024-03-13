<?php

namespace App\Admin\Controllers;

use App\Models\Audit;
use App\Models\Channel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class AuditController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "编单审核记录";

    protected $description = [
                'index'  => "查看审核记录及详细结果",
        //        'show'   => 'Show',
        //        'edit'   => 'Edit',
        //        'create' => 'Create',
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Audit());

        $grid->model()->with('channel')->orderBy('audit.id', 'desc');

        $grid->column('id', __('ID'));
        $grid->column('name', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');;

        $grid->column('air_date', __('Air date'))->display(function () {
            return $this->channel->air_date;
        });
 
        $grid->column('status', __('Status'))->using(Audit::STATUS)->label(['warning','success','danger']);

        $grid->column('reason', __('Reason'))->display(function () {
            return "展开";
        })->expand(function($model) {
            $data = json_decode($model->reason, true);
            $head = ['开始时间','结束时间','名称','播出编号','原时长','调整时长',''];
            $rows = [];
            if($data['duration']['result']) {
                return "<p>没有错误</p>";
            }
            else
            {
                foreach($data['duration']['logs'] as $item) {
                    $rows[] = [
                        $item['start_at'], $item['end_at'], $item['name'], $item['unique_no'], $item['duration'], $item['duration2'], ''
                    ];
                }
                return new Table($head, $rows);
            }
        });
        
        //$grid->column('audit_status', __('Audit status'))->filter(Channel::AUDIT)->using(Channel::AUDIT)->label(['info','success','danger']);;
        $grid->column('created_at', __('Created at'))->sortable()->hide();
        $grid->column('updated_at', __('Updated at'))->sortable();

        $grid->disableCreateButton();

        $grid->batchActions(function (Grid\Tools\BatchActions $actions) {
            
            $actions->disableDelete();
        });

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
        });

        $grid->tools(function (Grid\Tools $tools) {
            //$tools->disableBatchActions();
            
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
        $show = new Show(Audit::findOrFail($id));

        $show->field('id', __('Id'));
        
        $show->field('name', __('Group'))->using(Channel::GROUPS);
        $show->field('channel_id', __('Channel'));
        $show->field('status', __('Status'))->using(Channel::STATUS);
        $show->field('admin', __('Reviewer'));
        $show->field('reason', __('Comment'));
        
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
        $form = new Form(new Audit());

        $form->radio('name', __('Group'))->options(Channel::GROUPS);
        $form->text('channel_id', __('Channel'));
        $form->radio('status', __('Status'))->options(Audit::STATUS)->required();
        
        $form->divider(__('AuditInfo'));
        $form->text('admin', __('Reviewer'));
        
        //$form->date('audit_date', __('Audit date'));
        $form->textarea('reason', __('Comment'));


        return $form;
    }
}
