<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Material\Importer;
use App\Models\Category;
use App\Models\Material;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;
class MaterialController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '物料管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Material());

        $grid->model()->orderBy('id', 'desc');
        //$grid->column('id', __('Id'));
        $grid->column('unique_no', __('Unique_no'));
        $grid->column('name', __('Name'));  
        $grid->column('category', __('Category'))->display(function ($category) {
            return Category::findCategory($category). '&nbsp;('.$category.')';
        });
        $grid->column('duration', __('Duration'));
        $grid->column('size', __('Size'));
        $grid->column('frames', __('Frames'));
        $grid->column('created_at', __('Created at'));
        //$grid->column('updated_at', __('Updated at'));

        //$grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->add(new Importer);
        });

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->like('name', __('Name'));
            $filter->equal('unique_no', __('Unique_no'));
            $filter->equal('category', __('Category'))->select(Category::getFormattedCategories());
        
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
        $show = new Show(Material::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('unique_no', __('Unique no'));
        $show->field('category', __('Category'))->using(Category::getFormattedCategories());
        $show->field('duration', __('Duration'));
        $show->field('size', __('Size'));
        $show->field('frames', __('Frames'));
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
        $form = new Form(new Material());

        $form->text('name', __('Name'))->required();
        $form->text('unique_no', __('Unique no'))->required();
        $form->select('category', __('Category'))->options(Category::getFormattedCategories())->required();
        $form->text('duration', __('Duration'))->required();
        $form->text('frames', __('Frames'))->default(0);
        $form->text('size', __('Size'))->default(0);
        
        $form->saving(function(Form $form) {

            if($form->isCreating()) {
                $error = new MessageBag([
                    'title'   => '创建物料失败',
                    'message' => '该'.__('Unique no').': '. $form->unique_no.' 已存在。'
                ]);
    
                if(Material::where('unique_no', $form->unique_no)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改物料失败',
                    'message' => '该'.__('Unique no').': '. $form->unique_no.' 已存在。'
                ]);
    
                if(Material::where('unique_no', $form->air_date)->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }
            
        });
        
        return $form;
    }
}
