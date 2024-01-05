<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Channel\ToolEpgList;
use App\Models\Channel;
use App\Models\ExportList;
use App\Tools\Exporter;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;

class ChannelXmlController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "节目单 Xml 文件列表";

    protected $description = [
                'index'  => "查看和生成节目单 EPG 播出数据",
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

        $grid->model()->orderBy('air_date', 'desc');

        $grid->column('id', __('ID'));
        $grid->column('name', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        $grid->column('uuid', __('Uuid'))->display(function($uuid) {
            return '<a href="#">'.$uuid.'</a>';
        })->hide();
        $grid->column('air_date', __('Air date'))->sortable();
        //$grid->column('name', __('Name'));
        $grid->column('status', __('Status'))->filter(Channel::STATUS)->using(Channel::STATUS)->label(['warning','danger','success','danger']);
        //$grid->column('comment', __('Comment'));
        $grid->column('version', __('Version'))->label('default');
        $grid->column('reviewer', __('Reviewer'));
        $grid->column('audit_status', __('Audit status'))->filter(Channel::AUDIT)->using(Channel::AUDIT)->label(['info','success','danger']);;
        /*$grid->column('audit_date', __('Audit date'));
        $grid->column('distribution_date', __('Distribution date'));
        $grid->column('created_at', __('Created at'));
        */
        $grid->column('download', __('Download'))->display(function() {
            $filename = $this->name.'_'.$this->air_date.'.xml';
            return Storage::disk('public')->exists($filename) ? 
                '<a href="'.Storage::disk('public')->url($filename).'" target="_blank">'.
                $filename. ' ('.Exporter::filesize(Storage::disk('public')->size($filename)) . ')</a>':'';
        });

        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->batchActions(function (Grid\Tools\BatchActions $actions) {
            $actions->add(new ToolEpgList());
            $actions->disableDelete();
        });

        $grid->filter(function(Grid\Filter $filter){
            $filter->column(6, function (Grid\Filter $filter) {
                $filter->equal('name', __('Group'))->select(Channel::GROUPS);
                $filter->date('air_date', __('Air date'));
            });
            $filter->column(6, function (Grid\Filter $filter) {
                $filter->equal('uuid', __('Uuid'));
            });
            
        });

        $grid->tools(function (Grid\Tools $tools) {
            //$tools->disableBatchActions();
            
        });

        $grid->disableCreateButton();
        //$grid->disableBatchActions();
    
        $grid->disableActions();

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
        $show->field('name', __('Name'));
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

        $form->hidden('name', __('Name'))->default('channelv');
        $form->text('uuid', __('Uuid'))->default((string) Str::uuid())->required();
        $form->date('air_date', __('Air date'))->required();      
        $form->radio('status', __('Status'))->options(Channel::STATUS)->required();
        $form->text('version', __('Version'))->default('1')->required();

        $form->divider(__('AuditInfo'));
        $form->text('reviewer', __('Reviewer'));
        $form->radio('audit_status', __('Audit status'))->options(Channel::AUDIT)->required();
        $form->date('audit_date', __('Audit date'));
        $form->text('comment', __('Comment'));

        $form->date('distribution_date', __('Distribution date'));

        $form->saving(function(Form $form) {

            if($form->isCreating()) {
                $error = new MessageBag([
                    'title'   => '创建节目单失败',
                    'message' => '该日期 '. $form->air_date.' 节目单已存在。',
                ]);

                //$form->name = 'channelv';
    
                if(Channel::where('air_date', $form->air_date)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改节目单失败',
                    'message' => '该日期 '. $form->air_date.' 节目单已存在。',
                ]);
    
                if(Channel::where('air_date', $form->air_date)->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            //return $form;
            
        });

        return $form;
    }
}
