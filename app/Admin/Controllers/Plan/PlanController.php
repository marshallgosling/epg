<?php

namespace App\Admin\Controllers\Plan;

use App\Events\PlanEvent;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Plan;
use App\Models\TemplateRecords;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class PlanController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '播出计划 (暂时停用）';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Plan());

        $grid->model()->where('type', Plan::TYPE_PROGRAMS)->orderBy('id', 'desc');

        $grid->column('id', __('Id'))->hide();
        $grid->column('group_id', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        $grid->column('name', __('Name'));
        $grid->column('ex', __(" "))->display(function() {
            return "计划列表";
        })->expand(function ($model) {
            $programs = json_decode($model->data);
            $items = [];
            if($programs && is_array($programs))foreach($programs as $p)
            {            
                $items[] = [ $p->start_at, $p->end_at, $p->artist, $p->name, $p->unique_no, $p->duration];
            }

            return new Table([ '开始时间', '结束时间', '剧集', '选集', '播出编号', '时长'], $items, ['table-hover']);
        });
        $grid->column('date_from', __('Date from'));
        $grid->column('date_to', __('Date to'));

        $grid->column('start_at', __('Start at'));
        $grid->column('end_at', __('End at'));
        
        $grid->column('category', __('Category'))->hide();
        $grid->column('daysofweek', __('Daysofweek'))->display(function ($days) {
            $html = []; foreach($days as $d) $html[] = TemplateRecords::DAYS[$d];
            return implode(',', $html);
        });
        $grid->column('episodes', __('Episodes'))->hide();
        $grid->column('status', __('Status'))->filter(Plan::STATUS)->using(Plan::STATUS)->label(['default','success','warning','danger']);
        
        //$grid->column('type', __('Type'))->filter(TemplateRecords::TYPES)->using(TemplateRecords::TYPES);
        $grid->column('is_repeat', '是否循环')->bool();
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();

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
        $show->field('group_id', __('Group'));
        $show->field('name', __('Name'));

        $show->divider();
        $show->field('type', __('Type'));
        $show->field('category', __('Category'));
        $show->field('episodes', __('Episodes'));

        $show->divider('播出时间及周期');
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        $show->field('date_from', __('Date from'));
        $show->field('date_to', __('Date to'));
        $show->field('daysofweek', __('Daysofweek'))->as(function($days) {
            $html = []; foreach($days as $d) $html[] = TemplateRecords::DAYS[$d];
            return implode(',', $html);
        });
        
        $show->divider('状态及数据');
        $show->field('status', __('Status'));
        $show->field('data', __('Data'))->json();
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

        $form->radio('group_id', __('Group'))->options(Channel::GROUPS)->required();
        $form->text('name', __('Name'))->required();
        $form->hidden('type','')->default(Plan::TYPE_PROGRAMS);
        $form->divider('播出节目信息');
        // $form->radio('type', __('Type'))->options(TemplateRecords::TYPES)->default(0)->when(0, function (Form $form) { 
  
        //     $form->select('episodes', __('Episodes'))->options('/admin/api/episodes');
    
        // })->when(2, function (Form $form) { 
    
        //     $form->text('unique_no', __('Unique no'));
    
        // })->required();
        $form->select('category', __('Category'))->options(Category::getFormattedCategories());

        $form->divider('播出时间及周期');
        $form->text('start_at', __('Start at'))->inputmask(['mask'=>'99:99:99'])->required();
        $form->text('end_at', __('End at'))->inputmask(['mask'=>'99:99:99'])->required();
        $form->dateRange('date_from', 'date_to', __('DateRange'))->required();
       
        $form->checkbox('daysofweek', __('Daysofweek'))->options(TemplateRecords::DAYS);
        
        $form->divider('状态及数据');
        $form->radio('status', __('Status'))->options(Plan::STATUS)->required();
        $states = [
            'on'  => ['value' => 1, 'text' => '是', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'success'],
        ];
        $form->switch('is_repeat', '是否循环')->options($states);
        //$form->json('data', __('Data'));

        $form->saving(function (Form $form) {

            if($form->type != 2) {
                $form->episodes = $form->unique_no;
                
            }
            $form->ignore(['unique_no']);
        
        });

        $form->saved(function (Form $form) {
            PlanEvent::dispatch($form->model()->id);
        });

        return $form;
    }
}
