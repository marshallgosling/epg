<?php

namespace App\Admin\Controllers\Plan;

use App\Events\PlanEvent;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Plan;
use App\Models\TemplateRecords;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Form\EmbeddedForm;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class AdvertisePlanController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '广告播出计划';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Plan());

        $grid->model()->with('template')->where('type', Plan::TYPE_ADVERTISE)->orderBy('id', 'desc');

        $grid->column('id', __('Id'))->hide();
        $grid->column('group_id', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        $grid->column('name', __('Name'));

        $grid->column('start_at', __('Start at'));
        $grid->column('end_at', __('End at'));
        // $grid->column('ex', __(" "))->display(function() {
        //     return "计划列表";
        // })->expand(function ($model) {
        //     $programs = json_decode($model->data);
        //     $items = [];
        //     if($programs && is_array($programs))foreach($programs as $p)
        //     {            
        //         $items[] = [ $p->start_at, $p->end_at, $p->artist, $p->name, $p->unique_no, $p->duration];
        //     }

        //     return new Table([ '开始时间', '结束时间', '剧集', '选集', '播出编号', '时长'], $items, ['table-hover']);
        // });
        // $grid->column('date_from', __('Date from'));
        // $grid->column('date_to', __('Date to'));
        $grid->column('category', __('Template'))->display(function() {
            return $this->template->name;
        });
        $grid->column('is_repeat', __('Type'))->using(['单个','多集']);
        
        $grid->column('episodes', __('Episodes'))->display(function() {
            return $this->is_repeat ? $this->episodes:$this->data;
        });

        
        $grid->column('daysofweek', __('Daysofweek'))->display(function ($days) {
            $html = []; foreach($days as $d) $html[] = TemplateRecords::DAYS[$d];
            return implode(',', $html);
        });
        //$grid->column('episodes', __('Episodes'))->hide();
        $grid->column('status', __('Status'))->filter(Plan::STATUS)->using(Plan::STATUS)->label(['default','success','warning','danger']);
        
        //$grid->column('type', __('Type'))->filter(TemplateRecords::TYPES)->using(TemplateRecords::TYPES);
        //$grid->column('is_repeat', '是否循环')->bool();
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();

        // $grid->disableBatchActions();
        // $grid->disableCreateButton();
        // $grid->actions(function ($action) {

        // });

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
        $show = new Show(Plan::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('group_id', __('Group'))->using(Channel::GROUPS);
        $show->field('name', __('Name'));
        $show->field('status', __('Status'));

        $show->divider('播出时间');
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        // $show->field('date_from', __('Date from'));
        // $show->field('date_to', __('Date to'));
        
        $show->divider('广告节目配置');
        $show->field('category', __('Template'));
        $show->field('is_repeat', __('Type'))->using(['单个','多集']);
        $show->field('data', __('Unique no'));
        $show->field('episodes', __('Episodes'));
        $show->field('daysofweek', __('Daysofweek'))->as(function($days) {
            $html = []; foreach($days as $d) $html[] = TemplateRecords::DAYS[$d];
            return implode(',', $html);
        });
        // $show->field('data', __('Data'))->json();
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
        $form = new Form(new Plan());

        $form->select('group_id', __('Group'))->options(Channel::GROUPS)->load('category', '/admin/api/template')->required();
        $form->text('name', __('Name'))->required();

        $form->divider('播出时间');
        $form->hidden('type', __('Type'))->default(Plan::TYPE_ADVERTISE);
        $form->date('start_at', __('Start at'))->required();
        $form->date('end_at', __('End at'))->required();
        $form->hidden('date_from', __('Start at'))->default('2024');
        $form->hidden('date_to', __('End at'))->default('2024');

        $form->divider('广告节目配置');
        $form->select('category', __('Template'));

        $form->radio('is_repeat', __('Type'))->options(['单个','多集'])->default(0)->when(0, function (Form $form) {
            $form->text('data', __('Unique no'));
        })->when(1, function (Form $form) { 
            $form->select('episodes', __('Episodes'))->options('/admin/api/episodes');
        })->required();
        
        $form->checkbox('daysofweek', __('Daysofweek'))->options(TemplateRecords::DAYS)->canCheckAll();

        return $form;
    }
}
