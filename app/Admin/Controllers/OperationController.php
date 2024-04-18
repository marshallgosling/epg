<?php

namespace App\Admin\Controllers;

use App\Models\Agreement;
use App\Tools\Operation;
use Encore\Admin\Auth\Database\OperationLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Arr;

class OperationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '管理员操作日志';

    public static $methodColors = [
        'action'    => 'green',
        'post'   => 'yellow'
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OperationLog());

        $grid->model()->orderBy('id', 'DESC');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.name', '管理员');
        $grid->column('method', 'Method')->display(function ($method) {
            $color = Arr::get(OperationController::$methodColors, $method, 'grey');

            return "<span class=\"badge bg-$color\">$method</span>";
        })->filter(['action'=>'Action', 'post'=>'Controller']);
        $grid->column('path', 'Path');
        //$grid->column('ip')->label('primary');
        $grid->column('input', '数据')->display(function ($input) {
            return "展开";
        })->expand(function ($model) {
            return new Box('Json', view('admin.form.config', ['id'=>$model->id,'config'=>json_encode(json_decode($model->input), JSON_UNESCAPED_UNICODE)]));
        });

        $grid->column('created_at', trans('admin.created_at'));

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        $grid->disableCreateButton();
        $grid->disableBatchActions();

        $grid->filter(function (Grid\Filter $filter) {
            $userModel = config('admin.database.users_model');

            $filter->equal('user_id', 'User')->select($userModel::all()->pluck('name', 'id'));
            $filter->in('method')->checkbox(['action'=>'Action', 'post'=>'Controller']);
            $filter->like('path');
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
        $show = new Show(OperationLog::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user.name', __('User'));
        $show->field('method', __('Method'))->using(['action'=>'Action', 'post'=>'Controller']);
        $show->field('input', __('Data'))->json();
        
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
        $form = new Form(new OperationLog());

        

        return $form;
    }
}
