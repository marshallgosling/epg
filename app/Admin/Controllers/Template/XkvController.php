<?php

namespace App\Admin\Controllers\Template;

use App\Admin\Actions\Template\BatchDisable;
use App\Admin\Actions\Template\BatchEnable;
use App\Models\Template;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Widgets\InfoBox;
use App\Admin\Actions\Template\Programs;
use App\Admin\Actions\Template\ReplicateTemplate;
use App\Tools\ChannelGenerator;

class XkvController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '【 XKV 】模版';

    private $group = 'xkv';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Template());

        $grid->model()->where('group_id', $this->group)->orderBy('sort', 'asc');
 
        $grid->column('name', __('Name'))->display(function($name) {
            return '<a href="xkv/programs?template_id='.$this->id.'">'.$name.'</a>'; 
        });
        
        $grid->column('version', __('Version'))->display(function ($version) {
            return '<span class="label label-default">'.$version.'</span>';
        });
        $grid->column('start_at', __('Start at'))->display(function($start_at) {
            $today = strtotime(date('Y-m-d 6:00:00'));
            $air = strtotime(date('Y-m-d '.$start_at));
            $html = $start_at;
            if( $air < $today ) $html .= ' <span class="label label-default">次日</span>';
            return $html;
        });
        $grid->column('end_at', __('End at'))->hide();
        $grid->column('duration', __('Duration'));
        $grid->column('schedule', __('Schedule'))->using(Template::SCHEDULES)->filter(Template::SCHEDULES);
        $grid->column('sort', __('Sort'));
        $grid->column('status', __('Status'))->using(Template::STATUSES)->filter(Template::STATUSES)->label([
            Template::STATUS_NOUSE => 'default',
            Template::STATUS_SYNCING => 'success',
            Template::STATUS_STOPED => 'danger'
        ]);
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'));

        $grid->filter(function(Grid\Filter $filter){
            $filter->column(6, function (Grid\Filter $filter) {
                $filter->equal('schedule', __('Schedule'))->radio(Template::SCHEDULES);
                $filter->in('status',  __('Status'))->checkbox(Template::STATUSES);
            });
            
            $filter->column(6, function (Grid\Filter $filter) {
                $filter->like('name', __('Name'));
            });
        });

        $grid->actions(function ($actions) {
            $actions->add(new Programs);
            $actions->add(new ReplicateTemplate);
        });

        $grid->batchActions(function ($actions) {
            $actions->add(new BatchEnable);
            $actions->add(new BatchDisable);
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
        $show = new Show(Template::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('schedule', __('Schedule'))->using(Template::SCHEDULES);
        $show->field('start_at', __('Start at'));
        $show->field('duration', __('Duration'));
        $show->field('version', __('Version'));
        $show->field('sort', __('Sort'));
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
        $form = new Form(new Template());

        $form->text('version', __('Version'))->disable();
        $form->text('name', __('Name'))->required();
        $form->radio('schedule', __('Schedule'))->options(Template::SCHEDULES)->required();
        
        $form->text('start_at', __('Start at'))->required();
        $form->text('duration', __('Duration'))->inputmask(['mask' => '99:99:99'])->required();

        $form->number('sort', __('Sort'))->min(0)->default(0);
        $form->text('comment', __('Comment'));
        $form->hidden('group_id', __('Group'))->default($this->group);
        $form->hidden('end_at', __('End at'));
        
        $form->saving(function(Form $form) {

            $start = strtotime('2020/01/01 '.$form->start_at);
            $start += ChannelGenerator::parseDuration($form->duration);

            $form->end_at = date('H:i:s', $start);
            
        });

        return $form;
    }
}
