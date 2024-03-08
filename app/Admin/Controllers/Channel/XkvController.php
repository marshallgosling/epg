<?php

namespace App\Admin\Controllers\Channel;

use App\Admin\Actions\Channel\BatchAudit;
use App\Admin\Actions\Channel\BatchClean;
use App\Admin\Actions\Channel\CheckXml;
use App\Admin\Actions\Channel\Clean;
use App\Admin\Actions\Channel\Generator;
use App\Admin\Actions\Channel\ToolExporter;
use App\Admin\Actions\Channel\ToolGenerator;
use App\Models\Channel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;

class XkvController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "【 V China 】节目单";
    private $group = 'xkv';

    protected $description = [
                'index'  => "查看和编辑每日节目单数据",
        //        'show'   => 'Show',
        //        'edit'   => 'Edit',
        //        'create' => 'Create',
    ];

    public function preview($air_date, Content $content)
    {
        $model = Channel::where('name', $this->group)->where('air_date', $air_date)->first();

        $data = $model->programs()->get();
        $color = 'info';
          
        return $content->title(__('Preview EPG Content'))->description(__(' '))
        ->body(view('admin.epg.'.$this->group, compact('data', 'model', 'color')));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Channel());

        $grid->model()->where('name', $this->group)->orderBy('air_date', 'desc');

        $grid->column('id', __('编单'))->display(function($id) {
            return '<a href="'.$this->name.'/programs?channel_id='.$id.'">查看编单</a>';
        });
        $grid->column('air_date', __('Air date'))->display(function($air_date) {
            return '<a href="'.$this->name.'/preview/'.$air_date.'" title="预览EPG" data-toggle="tooltip" data-placement="top">'.$air_date.'</a>';
        });
        $grid->column('start_end', __('StartEnd'));
        $grid->column('status', __('Status'))->filter(Channel::STATUS)->using(Channel::STATUS)->label(['default','info','success','danger','warning'], 'info');
        //$grid->column('comment', __('Comment'));
        $grid->column('version', __('Version'))->label('default');
        $grid->column('reviewer', __('Reviewer'))->hide();
        $grid->column('audit_status', __('Audit status'))->filter(Channel::AUDIT)->using(Channel::AUDIT)->label(['info','success','danger']);;
        $grid->column('audit_date', __('Audit date'))->hide();
        $grid->column('status', __('操作'))->display(function() {return '校对';})->modal('检查播出串联单', CheckXml::class);

        $grid->column('distribution_date', __('Distribution date'));
        $grid->column('created_at', __('Created at'))->hide();
        
        $grid->column('updated_at', __('Updated at'));

        $grid->actions(function ($actions) {
            $actions->add(new Generator);
            $actions->add(new Clean);
        });

        $grid->batchActions(function ($actions) {
            //$actions->add(new BatchGenerator());
            $actions->add(new BatchClean);
        });

        $grid->filter(function($filter){
            $filter->column(6, function(Grid\Filter $filter) { 
                $filter->equal('uuid', __('Uuid'));
                $filter->date('air_date', __('Air date'));
            });
            
        });

        $grid->disableCreateButton();

        $grid->tools(function (Grid\Tools $tools) {
            //$tools->append(new ToolCreator('xkv'));
            $tools->append(new BatchAudit());
            $tools->append(new ToolExporter('xkv'));
            $tools->append(new ToolGenerator('xkv'));
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

        $form->hidden('name', __('Name'))->default($this->group);
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
    
                if(Channel::where('air_date', $form->air_date)->where('name', 'xkv')->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改节目单失败',
                    'message' => '该日期 '. $form->air_date.' 节目单已存在。',
                ]);
    
                if($form->model()->audit_status == Channel::AUDIT_PASS) {
                    $error = new MessageBag([
                        'title'   => '修改节目单失败',
                        'message' => '该日期 '. $form->air_date.' 节目单已锁定，无法修改。请先取消审核通过状态。',
                    ]);
                    return back()->with(compact('error'));
                }

                if(Channel::where('air_date', $form->air_date)->where('name', 'xkv')->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            //return $form;
            
        });

        return $form;
    }
}
