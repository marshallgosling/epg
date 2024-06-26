<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\Material\BatchCreator;
use App\Admin\Actions\Material\ToolScan;
use App\Models\Folder;
use App\Models\RawFiles;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RawFilesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '播出池文件';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RawFiles());
        $grid->model()->orderBy('created_at', 'desc');

        $grid->column('filename', __('Filename'));
        $grid->column('status', __('Status'))->bool();
        $grid->column('name', __('Name'));
        $grid->column('unique_no', __('Unique no'))->sortable();
        $grid->column('created_at', __('Created at'))->sortable();
        $grid->column('updated_at', __('Updated at'))->sortable()->hide();

        $grid->filter(function(Grid\Filter $filter) {
            $filter->column(6, function (Grid\Filter $filter) {
                $filter->equal('folder_id', __('播出池'))->select(Folder::pluck('path', 'id'));
                $filter->like('filename', __('Filename'))->placeholder('输入关键字搜索');
            });
        });

        $grid->batchActions(function (Grid\Tools\BatchActions $actions) {
            $actions->disableDelete();
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new BatchCreator);
            if(array_key_exists('folder_id', $_REQUEST))
                $tools->append(new ToolScan($_REQUEST['folder_id']));
        });

        $grid->disableCreateButton();
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
        $show = new Show(RawFiles::findOrFail($id));



        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new RawFiles());



        return $form;
    }
}
