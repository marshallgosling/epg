<?php

namespace App\Admin\Controllers\Channel;

use App\Admin\Actions\Channel\BatchAudit;
use App\Admin\Actions\Channel\BatchClean;
use App\Admin\Actions\Channel\Clean;
use App\Admin\Actions\Channel\ToolExporter;
use App\Admin\Actions\Channel\ToolGenerator;
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

class XkiController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "【 星空国际 】节目单";

    protected $description = [
                'index'  => "查看和编辑每日节目单数据",
        //        'show'   => 'Show',
        //        'edit'   => 'Edit',
        //        'create' => 'Create',
    ];

    public function preview($air_date, Content $content)
    {
        $model = Channel::where('name', 'xki')->where('air_date', $air_date)->first();

        $data = $model->programs()->get();
        $color = 'danger';
          
        return $content->title(__('Preview EPG Content'))->description(__(' '))
        ->body(view('admin.epg.xki', compact('data', 'model', 'color')));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Channel());

        $grid->model()->where('name', 'xki')->orderBy('air_date', 'desc');

        $grid->column('id', __('编单'))->display(function($id) {
            return '<a href="xki/programs?channel_id='.$id.'">查看编单</a>';
        });
        $grid->column('air_date', __('Air date'))->display(function($air_date) {
            return '<a href="xki/preview/'.$air_date.'" title="预览EPG" data-toggle="tooltip" data-placement="top">'.$air_date.'</a>';
        });

        $grid->column('start_end', __('StartEnd'));
        $grid->column('status', __('Status'))->filter(Channel::STATUS)
        ->using(Channel::STATUS)->label(['default','info','success','danger','warning'], 'info');
        //$grid->column('comment', __('Comment'));
        $grid->column('version', __('Version'))->label('default');
        $grid->column('reviewer', __('Reviewer'));
        $grid->column('audit_status', __('Audit status'))->filter(Channel::AUDIT)->using(Channel::AUDIT)->label(['info','success','danger']);;
        $grid->column('audit_date', __('Audit date'))->hide();
        $grid->column('distribution_date', __('Distribution date'));
        $grid->column('created_at', __('Created at'))->hide();
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
            //$tools->append(new ToolCreator('xki'));
            $tools->append(new BatchAudit);
            $tools->append(new ToolExporter('xki'));
            $tools->append(new ToolGenerator('xki'));
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

        $form->hidden('name', __('Name'))->default('xki');
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
    
                if(Channel::where('air_date', $form->air_date)->where('name', 'xki')->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改节目单失败',
                    'message' => '该日期 '. $form->air_date.' 节目单已存在。',
                ]);
    
                if(Channel::where('air_date', $form->air_date)->where('name', 'xki')->where('id','<>',$form->model()->id)->exists())
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
        $generator = new TableGenerator('xki');
        $st = strtotime($request->get('start_at', ''));
        $ed = strtotime($request->get('end_at', ''));
        $label_st = '';
        $label_ed = '';

        $max = (int)config('MAX_EXPORT_DAYS', 7);
        if($max < 7) $max = 7;

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
        $table = $generator->export($days, $template, $data);
        }

        $filter= <<<FILTER
        <div class="box-header with-border filter-box" id="filter-box">
    <form action="export" class="form-horizontal" pjax-container method="get">

        <div class="row">
                        <div class="col-md-8">
                <div class="box-body">
                    <div class="fields-group">
                                                    <div class="form-group">
    <label class="col-sm-2 control-label">时间范围</label>
    <div class="col-sm-8">
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
DATE;
}

