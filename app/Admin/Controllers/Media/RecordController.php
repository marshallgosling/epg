<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\Program\BatchModify;
use App\Models\Record;
use App\Models\Category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;

class RecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'XKC 节目库管理';

    protected $description = [
        'index'  => "XKC/XKI 节目库数据",
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
        $grid = new Grid(new Record());
        
        $grid->model()->orderBy('id', 'desc');
        //$grid->column('id', __('Id'));
        $grid->column('unique_no', __('Unique no'))->sortable()->width(200);
        $grid->column('name', __('Name'))->sortable();
        $grid->column('category', __('Category'))->display(function($category) {
            $category = array_map(function ($c) {
                $t = Category::findCategory($c);
                return '<span class="label label-info" title="'.$t.'" data-toggle="tooltip" data-placement="top">'.$c.'</span>';
            }, $category);

            $black = $this->black ? "<span class='label label-danger'>黑名单</span>" : '';
            return $black.'&nbsp;'.join('&nbsp;', $category);
        });
        $grid->column('episodes', __('Episodes'))->sortable();
        $grid->column('ep', __('Ep'))->sortable();
        $grid->column('duration', __('Duration'))->sortable();
        
        $grid->column('air_date', __('Air date'))->hide();
        $grid->column('expired_date', __('Expired date'))->hide();

        $grid->column('created_at', __('Created at'))->hide()->sortable();
        $grid->column('updated_at', __('Updated at'))->hide()->sortable();

        $grid->filter(function(Grid\Filter $filter){

            $filter->column(6, function(Grid\Filter $filter) { $filter->mlike('name', __('Name'))->placeholder('输入%作为通配符，如 灿星% 或 %灿星%'); $filter->like('category', __('Category'))->select(Category::getFormattedCategories('tags', true)); });
            $filter->column(6, function(Grid\Filter $filter) { $filter->startsWith('unique_no', __('Unique_no'))->placeholder('仅支持左匹配'); });
    
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new BatchModify);
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
        $show = new Show(Record::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('unique_no', __('Unique no'));
        $show->field('name', __('Name'));
        $show->field('category', __('Category'))->implode(',');
        $show->field('duration', __('Duration'));
        
        $show->field('air_date', __('Air date'));
        $show->field('expired_date', __('Expired date'));
        $show->field('episodes', __('Episodes'));
        $show->field('ep', __('Ep'));
        $show->field('black', __('BlackList'));
        $show->field('comment', __('Comment'));
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
        $form = new Form(new Record());

        $form->text('unique_no', __('Unique no'))->required();
        $form->text('name', __('Name'))->required();
        
        $form->multipleSelect('category', __('Category'))->options(Category::getFormattedCategories('tags', true))->required();
        $form->text('duration', __('Duration'))->inputmask(['mask' => '99:99:99:99'])->required();
        $form->text('episodes', __('Episodes'));
        $form->text('ep', __('Ep'));

        $form->text('air_date', __('Air date'));
        $form->text('expired_date', __('Expired date'));

        $form->switch('black', __('BlackList'));

        $form->text('comment', __('Comment'));

        return $form;
    }
}
