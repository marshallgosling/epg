<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\Material\BatchScan;
use App\Models\Folder;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FolderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '播出池扫描文件列表管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Folder());

        $grid->column('id', __('Id'));
        $grid->column('path', __('Filepath'));
        $grid->column('status', __('Status'))->using(Folder::STATUS)->label(['default','success','warning','danger']);
        $grid->column('comment', __('Comment'));
        $grid->column('scaned_at', __('Scaned at'));
        $grid->column('link', '')->display(function () {
            return '<a href="./folder/'.$this->id.'">查看详细数据</a>';
        });
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new BatchScan);
        });

        $grid->batchActions(function (Grid\Tools\BatchActions $actions) {
            $actions->disableDelete();
        });

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
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
        $show = new Show(Folder::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('path', __('Filepath'));
        $show->field('status', __('Status'))->using(Folder::STATUS);
        $show->field('comment', __('Comment'));
        $show->field('scaned_at', __('Scaned at'));
        $show->json('data', __('Data'));

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
        $form = new Form(new Folder());

        $form->text('path', __('Filepath'));
        $form->radio('status', __('Status'))->options(Folder::STATUS);
        $form->text('comment', __('Comment'));
        $form->datetime('scaned_at', __('Scaned at'));
        $form->json('data', __('Data'));

        return $form;
    }
}
