<?php

namespace App\Admin\Controllers\Channel;

use App\Admin\Actions\Channel\BatchAudit;
use App\Admin\Actions\Channel\BatchClean;
use App\Admin\Actions\Channel\Clean;
use App\Admin\Actions\Channel\ToolExporter;
use App\Admin\Actions\Channel\BatchXkcGenerator as BatchGenerator;
use App\Admin\Actions\Channel\ToolCreator;
use App\Admin\Actions\Channel\ToolGenerator;
use App\Models\Channel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;

class XkcController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "【 星空中国 】节目单";

    protected $description = [
                'index'  => "查看和编辑每日节目单数据",
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
        $grid = new Grid(new Channel());

        $grid->model()->where('name', 'xkc')->orderBy('air_date', 'desc');

        $grid->column('uuid', __('Uuid'))->display(function($uuid) {
            return '<a href="xkc/programs?channel_id='.$this->id.'">'.$uuid.'</a>';
        });
        $grid->column('air_date', __('Air date'))->display(function($air_date) {
            return '<a href="'.admin_url('epg/preview/'.$this->id).'" title="预览EPG" data-toggle="tooltip" data-placement="top">'.$air_date.'</a>';
        });

        $grid->column('start_end', __('StartEnd'));
        $grid->column('status', __('Status'))->filter(Channel::STATUS)
        ->using(Channel::STATUS)->label(['default','info','success','danger','warning']);
        //$grid->column('comment', __('Comment'));
        $grid->column('version', __('Version'))->label('default');
        $grid->column('reviewer', __('Reviewer'));
        $grid->column('audit_status', __('Audit status'))->filter(Channel::AUDIT)->using(Channel::AUDIT)->label(['info','success','danger']);;
        /*$grid->column('audit_date', __('Audit date'));
        $grid->column('distribution_date', __('Distribution date'));
        $grid->column('created_at', __('Created at'));
        */
        $grid->column('updated_at', __('Updated at'));

        $grid->actions(function ($actions) {
            //$actions->add(new Generator);
            $actions->add(new Clean);
        });

        $grid->batchActions(function ($actions) {
            //$actions->add(new BatchGenerator());
            $actions->add(new BatchClean);
        });

        $grid->filter(function(Grid\Filter $filter){

            $filter->column(6, function(Grid\Filter $filter) { 
                $filter->equal('uuid', __('Uuid'));
                $filter->date('air_date', __('Air date'));
            });
            
        });

        $grid->disableCreateButton();

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new ToolCreator('xkc'));
            $tools->append(new BatchAudit);
            $tools->append(new ToolExporter('xkc'));
            $tools->append(new ToolGenerator('xkc'));
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
        $show = new Show(Channel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('uuid', __('Uuid'));
        $show->field('air_date', __('Air date'));
        $show->field('name', __('Group'));
        $show->field('status', __('Status'))->using(Channel::STATUS);
        $show->field('comment', __('Comment'));
        $show->field('version', __('Version'));
        $show->field('reviewer', __('Reviewer'));
        $show->field('audit_status', __('Audit status'))->using(Channel::AUDIT);
        $show->field('audit_date', __('Audit date'));
        $show->field('distribution_date', __('Distribution date'));
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
        $form = new Form(new Channel());

        $form->hidden('name', __('Name'))->default('xkc');
        $form->display('uuid', __('Uuid'))->default('自动生成');
        $form->date('air_date', __('Air date'))->required();      
        $form->radio('status', __('Status'))->options(Channel::STATUS)->required();
        $form->display('version', __('Version'))->default('1');

        $form->divider(__('AuditInfo'));
        $form->text('reviewer', __('Reviewer'));
        $form->radio('audit_status', __('Audit status'))->options(Channel::AUDIT)->required();
        $form->date('audit_date', __('Audit date'));
        $form->textarea('comment', __('Comment'));

        $form->date('distribution_date', __('Distribution date'));

        $form->saving(function(Form $form) {

            if($form->isCreating()) {
                $error = new MessageBag([
                    'title'   => '创建节目单失败',
                    'message' => '该日期 '. $form->air_date.' 节目单已存在。',
                ]);

                $form->uuid = (string) Str::uuid();
                $form->version = 1;
    
                if(Channel::where('air_date', $form->air_date)->where('name', 'xkc')->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改节目单失败',
                    'message' => '该日期 '. $form->air_date.' 节目单已存在。',
                ]);
    
                if(Channel::where('air_date', $form->air_date)->where('name', 'xkc')->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            //return $form;
            
        });

        return $form;
    }
}
