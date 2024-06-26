<?php

namespace App\Admin\Controllers\Channel;

use App\Admin\Actions\Channel\BatchAudit;
use App\Admin\Actions\Channel\BatchLock;
use App\Admin\Actions\Channel\BatchClean;
use App\Admin\Actions\Channel\BatchDistributor;
use App\Admin\Actions\Channel\BatchReplicate;
use App\Admin\Actions\Channel\CheckEpg;
use App\Admin\Actions\Channel\CheckXml;
use App\Admin\Actions\Channel\Clean;
use App\Admin\Actions\Channel\Generator;
use App\Admin\Actions\Channel\Lock;
use App\Admin\Actions\Channel\Replicate;
use App\Admin\Actions\Channel\ToolExporter;
use App\Admin\Actions\Channel\ToolGenerator;
use App\Models\Audit;
use App\Models\Channel;
use App\Tools\ChannelGenerator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\ContextMenuActions;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
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
        $list = [];
    
        foreach($data as &$program)
        {
            if(strpos($program->data, '"replicate"'))
            {
                //echo "{$program->name} {$program->id}\n";
                $replicate = json_decode($program->data);
                $json = json_decode($list[$replicate->replicate]);
                $air = strtotime($program->start_at);
                foreach($json as &$item)
                {
                    if(!$item) continue;
                    $item->start_at = date('H:i:s', $air);
                    $air += ChannelGenerator::parseDuration($item->duration);
                    $item->end_at = date('H:i:s', $air);
                }
                $program->data = json_encode($json);
            }
            else {
                $list[$program->id] = $program->data;
            }
        }

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

        $grid->model()->with('audit')->where('name', $this->group)->orderBy('air_date', 'desc');

        $grid->column('id', 'ID')->hide();
        $grid->column('version', __('Version'))->label('default')->width(50);
        $grid->column('lock_status', __('Lock'))->display(function($lock) {
            return $lock == Channel::LOCK_ENABLE ? '<i class="fa fa-lock text-danger"></i>':'<i class="fa fa-unlock-alt text-info"></i>';
        })->width(40);

        $grid->column('show', __('编单'))->display(function() {
            return '<a href="'.$this->name.'/programs?channel_id='.$this->id.'">查看编单</a>';
        })->width(100);
        
        $grid->column('air_date', __('Air date'))->display(function($air_date) {
            return '<a href="'.$this->name.'/preview/'.$air_date.'" title="预览EPG" data-toggle="tooltip" data-placement="top">'.$air_date.'</a>';
        })->width(100);

        $grid->column('start_end', __('StartEnd'))->width(130);
        $grid->column('status', __('Status'))->filter(Channel::STATUS)->width(60)
            ->using(Channel::STATUS)->label(['default','info','success','danger','warning'], 'info');
        
        $grid->column('audit', __('Audit status'))->width(100)->display(function () { 
            if($this->audit) {
                foreach($this->audit()->orderBy('id','desc')->get() as $item) {
                    return Audit::STATUS[$item->status];
                }
            }
            return Audit::STATUS[0];
        })->expand(function ($model) {
            $labels = ['warning', 'success', 'danger'];
            if(!$model->audit) return "<p>无审核记录</p>";
            $rows = [];
            foreach($model->audit()->orderBy('id','desc')->take(10)->get() as $item) {
                $rows[] = [
                    $item->id, '<span class="label label-'.$labels[$item->status].'">'.Audit::STATUS[$item->status].'</span>', 
                    $item->created_at, $item->comment, '<a href="./audit?channel_id='.$model->id.'">查看详细</a>'
                ];
            }
            $head = ['ID','审核结果','日期','备注说明',''];
            return new Table($head, $rows);
        });
        
        $grid->column('audit_date', __('Audit date'))->hide();
        $grid->column('check', __('操作'))->width(80)->display(function() {return '校对';})->modal('检查播出串联单', CheckEpg::class);

        $grid->column('distribution_date', __('Distribution date'))->sortable();
        $grid->column('comment', __('Comment'));
        $grid->column('created_at', __('Created at'))->hide();
        
        $grid->column('updated_at', __('Updated at'))->sortable();

        $grid->actions(function ($actions) {
            $actions->add(new Lock);
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
                $filter->equal('lock_status', __('Lock'))->radio(Channel::LOCKS);
            });
            
        });

        $grid->disableCreateButton();

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new ToolGenerator('xkv'));
            $tools->append(new BatchAudit());
            $tools->append(new BatchLock());
            $tools->append(new BatchDistributor());
            $tools->append(new ToolExporter('xkv'));
        });

        $grid->setActionClass(ContextMenuActions::class);

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
            
        });

        return $form;
    }
}
