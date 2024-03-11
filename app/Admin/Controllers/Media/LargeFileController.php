<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\Material\BatchProcessLargeFile;
use App\Models\LargeFile;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LargeFileController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '物料文件上传';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LargeFile());
        $grid->model()->orderBy('id', 'desc');
        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('status', __('Status'))->using(LargeFile::STATUS);
        $grid->column('storage', __('Storage'))->hide();
        $grid->column('path', __('FilePath'));
        $grid->column('target_path', __('TargetPath'))->using(explode(PHP_EOL, config('MEDIA_SOURCE_FOLDER', '')));
        $grid->column('created_at', __('Created at'))->sortable();
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->filter(function(Grid\Filter $filter){

            $filter->mlike('name', __('Name'))->placeholder('输入%作为通配符，如 灿星% 或 %灿星%');
            $filter->equal("status", __('Status'))->radio(LargeFile::STATUS);
        
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new BatchProcessLargeFile);
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
        $show = new Show(LargeFile::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('storage', __('Storage'));
        $show->field('path', __('Path'));
        $show->field('target_path', __('Target path'));
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
        $form = new Form(new LargeFile());

        $form->largefile('path', __('本地文件'));
        $form->text('name', __('文件名'))->placeholder('文件名格式：节目名.播出编号.mxf，上传完毕后自动填充。');
        $form->hidden('storage', __('Storage'))->default('local');
        
        $form->radio('target_path', __('TargetPath'))->options(explode(PHP_EOL, config('MEDIA_SOURCE_FOLDER', '')))->stacked();
        $form->textarea('comment', "说明")->default("文件名格式：节目名.播出编号.mxf，上传完毕后自动填充。\n请等待上传文件完成后，点击“提交”按钮")->disable();
        return $form;
    }
}
