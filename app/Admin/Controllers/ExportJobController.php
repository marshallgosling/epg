<?php

namespace App\Admin\Controllers;

use App\Models\ExportJob;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;

class ExportJobController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '串联单导出记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ExportJob());

        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('Id'))->sortable();
        $grid->column('name', __('Name'))->display(function ($name) {
            return $name.' <small>'.ExportJob::GROUPS[$this->group_id].'</small>';
        });
        $grid->column('start_at', __('Start at'))->sortable();
        $grid->column('end_at', __('End at'))->sortable();

        $grid->column('status', __('Status'))->using(ExportJob::STATUS)->label(['default','warning','success','danger']);
        
        $grid->column('filename', __('Filename'))->display(function($filename) {
            return $this->status == ExportJob::STATUS_READY ? '<a href="/admin/export/download/'.$this->id.'" target="_blank"><i class="fa fa-download"></i> '.__('Download').'</a>' : '';
        });        
        $grid->column('created_at', __('Created at'))->sortable();
        $grid->column('updated_at', __('Updated at'))->sortable()->hide();

        return $grid;
    }

    public function download($id) 
    {
        $file = ExportJob::findOrFail($id);

        $filename = $file->filename;

        if(!Storage::disk($file->group_id)->exists($filename)) {
            return response('文件不存在或者仍在处理中。', 404);
        }

        if($file->status == ExportJob::STATUS_READY) {
            return Storage::disk($file->group_id)->download($filename, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        }
        else {
            return response('文件仍在处理中。', 404);
        }
        

    }
    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ExportJob::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        $show->field('filename', __('Filename'));
        $show->field('group_id', __('Group id'))->using(ExportJob::GROUPS);
        $show->field('status', __('Status'))->using(ExportJob::STATUS);
        $show->field('reason', __('Reason'));
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
        $form = new Form(new ExportJob());

        $form->text('name', __('Name'));
        $form->date('start_at', __('Start at'))->required();
        $form->date('end_at', __('End at'))->required();
        $form->text('filename', __('Filename'));
        $form->radio('group_id', __('Group id'))->default('xkv')->options(ExportJob::GROUPS);
        $form->radio('status', __('Status'))->default(0)->options(ExportJob::STATUS);
        $form->textarea('reason', __('Reason'));

        return $form;
    }
}
