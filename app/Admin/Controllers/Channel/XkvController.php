<?php

namespace App\Admin\Controllers\Channel;

use App\Admin\Actions\Channel\BatchAudit;
use App\Admin\Actions\Channel\BatchLock;
use App\Admin\Actions\Channel\BatchClean;
use App\Admin\Actions\Channel\BatchDistributor;
use App\Admin\Actions\Channel\BatchReplicate;
use App\Admin\Actions\Channel\CheckXml;
use App\Admin\Actions\Channel\Clean;
use App\Admin\Actions\Channel\Generator;
use App\Admin\Actions\Channel\Replicate;
use App\Admin\Actions\Channel\ToolExporter;
use App\Admin\Actions\Channel\ToolGenerator;
use App\Models\Channel;
use App\Tools\ChannelGenerator;
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
    protected $title = "【 V China 】编单";
    private $group = 'xkv';

    protected $description = [
                'index'  => "查看和编辑每日编单数据",
        //        'show'   => 'Show',
        //        'edit'   => 'Edit',
        //        'create' => 'Create',
    ];

    public function preview($air_date, Content $content)
    {
        $model = Channel::where('name', $this->group)->where('air_date', $air_date)->first();

        $data = $model->programs()->get();
        $color = 'info';

        $miss = ChannelGenerator::checkMaterials($data);
          
        return $content->title(__('Preview EPG Content'))->description(__(' '))
        ->body(view('admin.epg.'.$this->group, compact('data', 'model', 'color', 'miss')));
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
        $grid->column('lock_status', __('Lock status'))->display(function($lock) {
            return $lock == Channel::LOCK_ENABLE ? '<i class="fa fa-lock text-warning"></i>':'<i class="fa fa-unlock-alt text-info"></i>';
        });
        $grid->column('audit_date', __('Audit date'))->hide();
        $grid->column('check', __('操作'))->display(function() {return '校对';})->modal('检查播出串联单', CheckXml::class);

        $grid->column('distribution_date', __('Distribution date'));
        $grid->column('created_at', __('Created at'))->hide();
        
        $grid->column('updated_at', __('Updated at'));

        $grid->actions(function ($actions) {
            $actions->add(new Generator);
            $actions->add(new Clean);
            $actions->add(new Replicate);
        });

        $grid->batchActions(function ($actions) {
            //$actions->add(new BatchGenerator());
            $actions->add(new BatchClean);
            $actions->add(new BatchReplicate);
        });

        $grid->filter(function($filter){
            $filter->column(6, function(Grid\Filter $filter) { 
                $filter->date('air_date', __('Air date'));
            });
            
        });

        $grid->disableCreateButton();

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new BatchDistributor());
            $tools->append(new BatchLock());
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
        $show->field('lock_status', __('Lock status'))->using(Channel::LOCKS);
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
        $form->radio('lock_status', __('Lock status'))->options(Channel::LOCKS)->required();
        $form->date('audit_date', __('Audit date'));
        $form->textarea('comment', __('Comment'));

        $form->date('distribution_date', __('Distribution date'));

        $form->saving(function(Form $form) {

            if($form->isCreating()) {
                $error = new MessageBag([
                    'title'   => '创建编单失败',
                    'message' => '该日期 '. $form->air_date.' 编单已存在。',
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
                    'title'   => '修改编单失败',
                    'message' => '该日期 '. $form->air_date.' 编单已存在。',
                ]);
    
                if($form->model()->lock_status == Channel::LOCK_ENABLE) {
                    $error = new MessageBag([
                        'title'   => '修改编单失败',
                        'message' => '该日期 '. $form->air_date.' 编单已锁定，无法修改。请先取消“锁"状态。',
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
