<?php

namespace App\Admin\Controllers\Media;

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

        $grid->column('filename', __('Filename'));
        $grid->column('status', __('Status'))->bool();
        $grid->column('name', __('Name'));
        $grid->column('unique_no', __('Unique no'))->sortable();
        $grid->column('created_at', __('Created at'))->sortable();
        $grid->column('updated_at', __('Updated at'))->sortable()->hide();

        $grid->filter(function($filter) {
            $filter->column(6, function (Grid\Filter $filter) {
                $filter->equal('folder_id', __('播出池'))->select(Folder::pluck('path', 'id'));
            });
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
