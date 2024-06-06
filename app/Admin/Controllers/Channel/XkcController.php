<?php

namespace App\Admin\Controllers\Channel;

use App\Admin\Actions\Channel\BatchAudit;
use App\Admin\Actions\Channel\BatchLock;
use App\Admin\Actions\Channel\BatchClean;
use App\Admin\Actions\Channel\BatchDistributor;
use App\Admin\Actions\Channel\CheckEpg;
use App\Admin\Actions\Channel\CheckXml;
use App\Admin\Actions\Channel\Clean;
use App\Admin\Actions\Channel\Lock;
use App\Admin\Actions\Channel\TemplateLink;
use App\Admin\Actions\Channel\ToolExporter;
use App\Admin\Actions\Channel\ToolGenerator;
use App\Models\Audit;
use App\Models\Channel;
use App\Tools\Exporter\TableGenerator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;
use App\Tools\ChannelGenerator;
use Encore\Admin\Grid\Displayers\ContextMenuActions;
use Encore\Admin\Widgets\Table;

class XkcController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "【 星空中国 】编单";

    private $group = 'xkc';

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
        $list = [];
    
        foreach($data as &$program)
        {
            if(strpos($program->data, 'replicate'))
            {
                $replicate = json_decode($program->data);
                $json = json_decode($list[$replicate->replicate]);
                $air = strtotime($program->start_at);
                foreach($json as &$item)
                {
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
        $color = 'primary';
        $miss = ChannelGenerator::checkMaterials($data);
        return $content->title(__('Preview EPG Content'))->description(__(' '))
        ->body(view('admin.epg.'.$this->group, compact('data', 'model', 'color','miss')));
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

        $grid->column('show', __('编单'))->display(function($id) {
            return '<a href="'.$this->name.'/programs?channel_id='.$this->id.'">查看编单</a>';
        })->width(90);
        
        $grid->column('air_date', __('Air date'))->display(function($air_date) {
            return '<a href="'.$this->name.'/preview/'.$air_date.'" title="预览EPG" data-toggle="tooltip" data-placement="top">'.$air_date.'</a>';
        })->width(90);

        $grid->column('start_end', __('StartEnd'))->width(140);
        $grid->column('status', __('Status'))->filter(Channel::STATUS)->width(60)
            ->using(Channel::STATUS)->label(['default','info','success','danger','warning'], 'info');
        
        $grid->column('audit', __('Audit status'))->width(90)->display(function () { 
            if($this->audit) {
                $item = $this->audit()->orderBy('id','desc')->first();
                if($item) {
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
        
        $grid->column('reviewer', __('Reviewer'))->hide();
        
        $grid->column('audit_date', __('Audit date'))->hide();
        
        $grid->column('check', __('操作'))->display(function() {return '校对';})->modal('检查播出串联单', CheckEpg::class)->width(80);
        $grid->column('distribution_date', __('Distribution date'))->sortable();
        $grid->column('comment', __('Comment'));
        $grid->column('created_at', __('Created at'))->sortable()->hide();
        $grid->column('updated_at', __('Updated at'))->sortable()->hide();

        $grid->actions(function ($actions) {
            $actions->add(new Lock);
            $actions->add(new TemplateLink);
        });

        $grid->batchActions(function (Grid\Tools\BatchActions $actions) {
            $actions->add(new BatchClean);
        });

        $grid->filter(function(Grid\Filter $filter){

            $filter->column(6, function(Grid\Filter $filter) { 
                $filter->date('air_date', __('Air date'));
                $filter->equal('lock_status', __('Lock'))->radio(Channel::LOCKS);
            });
            
        });

        $grid->disableCreateButton();

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new ToolGenerator('xkc'));
            $tools->append(new BatchAudit);
            $tools->append(new BatchLock);
            $tools->append(new BatchDistributor());
            $tools->append(new ToolExporter('xkc')); 
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
                    'message' => '编单不可手动创建。',
                ]);
                return back()->with(compact('error'));
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
    
                if(Channel::where('air_date', $form->air_date)->where('name', 'xkc')->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            //return $form;
            
        });

        return $form;
    }

    public function export(Content $content, Request $request)
    {
        $generator = new TableGenerator($this->group);
        $st = strtotime($request->get('start_at', ''));
        $ed = strtotime($request->get('end_at', ''));
        $lang = $request->get('lang', 'zh');
        $label_st = '';
        $label_ed = '';
        $zh_checked = '';
        $en_checked = '';
        if($lang == 'zh') $zh_checked = "checked";
        if($lang == 'en') $en_checked = "checked";

        $max = (int)config('MAX_EXPORT_DAYS', 7);

        if($st=='') $table = '<p></p>';
        else {
          $tmp = $st + $max * 86400 - 86400;
        if($ed>$tmp) $ed = $tmp;
        if($ed<$st) $ed = $tmp;

        $label_st = date('Y-m-d', $st);
        $label_ed = date('Y-m-d', $ed);

        $days = $generator->generateDays($st, $ed);
        $data = $generator->processData($days);
        $template = $generator->loadTemplate();
        $table = $generator->export($days, $template, $data, $lang);
        }

        $filter= <<<FILTER
        <div class="box-header with-border filter-box" id="filter-box">
    <form action="export" class="form-horizontal" pjax-container method="get">

        <div class="row">
            
            <div class="col-md-10">
                <div class="box-body">
                    <div class="fields-group">
                        <div class="form-group">
    <label class="col-sm-2 control-label">时间范围</label>
    <div class="col-sm-6">
        <div class="input-group input-group-sm">
            <div class="input-group-addon">
                <i class="fa fa-calendar"></i>
            </div>
            <input type="text" class="form-control" id="start_at_start" placeholder="时间范围" name="start_at" value="{$label_st}" autocomplete="off">

            <span class="input-group-addon" style="border-left: 0; border-right: 0;">-</span>

            <input type="text" class="form-control" id="start_at_end" placeholder="时间范围" name="end_at" value="{$label_ed}" autocomplete="off">
        </div>
    </div>

    <div class="col-sm-2">
    <div class="input-group input-group-sm">

    <span class="icheck">

        <label class="radio-inline">
            <input type="radio" class="language" name="lang" value="zh" {$zh_checked}> 中文  
        </label>

    </span>


    <span class="icheck">

        <label class="radio-inline">
            <input type="radio" class="language" name="lang" value="en" {$en_checked}> En  
        </label>

    </span>

    </div>
</div>
    <div class="col-sm-2">
      <div class="btn-group pull-left">
                            <button class="btn btn-info submit btn-sm"><i class="fa fa-search"></i>  搜索</button>
                        </div>
    </div>
</div>
                                            </div>
                </div>
            </div>
                    </div>
        <!-- /.box-body -->

    </form>
</div>
FILTER;
        
        $head = '<div class="box-header with-border clearfix">选择开始日期（必须）, 结束日期（可选），一次最多展示 <code>'.$max.'</code> 天</div>';
        $box = '<div class="col-md-12"><div class="box box grid-box">'.$filter.$head.'<div class="box-body table-responsive no-padding">'.$table.'</div></div></div>';
        \Encore\Admin\Admin::script(self::JS);
        return $content
            ->title($this->title.'查看工具')
            ->description('')
            ->row($box);
    
    }

    public const JS = <<<DATE
    $('#start_at_start').datetimepicker({"format":"YYYY-MM-DD","locale":"zh-CN"});
            $('#start_at_end').datetimepicker({"format":"YYYY-MM-DD","locale":"zh-CN","useCurrent":false});
            $("#start_at_start").on("dp.change", function (e) {
                $('#start_at_end').data("DateTimePicker").minDate(e.date);
            });
            $("#start_at_end").on("dp.change", function (e) {
                $('#start_at_start').data("DateTimePicker").maxDate(e.date);
            });
            $('.language').iCheck({radioClass:'iradio_minimal-blue'}); 
DATE;

}
