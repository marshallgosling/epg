<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\Material\ShowMaterial;
use App\Admin\Actions\Program\BatchModify;
use App\Events\CategoryRelationEvent;
use App\Models\Record;
use App\Models\Category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;

class XkcProgramController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '星空中国 节目库管理';

    protected $description = [
        'index'  => "星空中国 节目库数据",
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
        $grid->column('unique_no', __('Unique no'))->width(200)->modal(ShowMaterial::class);
        $grid->column('status', __('Status'))->display(function($status) {
            return $status == Record::STATUS_READY ? '<i class="fa fa-check text-green"></i>':'<i class="fa fa-close text-red"></i> ';
        });
        $grid->column('name', __('Name'))->display(function ($name) {
            if($this->name2) $name2 = '&nbsp; <small class="text-info" title="'.str_replace('"', '\\"', $this->name2).'" data-toggle="tooltip" data-placement="top">Eng</small>';
            else $name2 = '';
            return $name . $name2;
        });
        $grid->column('name2', __('English'))->hide();
        $grid->column('category', __('Category'))->display(function($category) {
            $category = array_map(function ($c) {
                $t = Category::findCategory($c);
                return '<span class="label label-info" title="'.$c.'" data-toggle="tooltip" data-placement="top">'.$t.'</span>';
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
        $grid->column('updated_at', __('Updated at'))->sortable();

        $grid->filter(function(Grid\Filter $filter){

            $filter->column(6, function(Grid\Filter $filter) { 
                $filter->clike('category', __('Category'))->select(Category::getFormattedCategories('tags', true)); 
                $filter->mlike('name', __('Name'))->placeholder('输入%作为通配符，如 灿星% 或 %灿星%');
                $filter->equal("status", __('Status'))->select(Record::STATUS);
            });
            $filter->column(6, function(Grid\Filter $filter) { 
                $filter->mlike('episodes', __('Episodes'))->placeholder('输入%作为通配符，如 灿星% 或 %灿星%'); 
                $filter->startsWith('unique_no', __('Unique_no'))->placeholder('仅支持左匹配'); 
            });
    
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
        $show->field('name2', __('English'));
        $show->field('category', __('Category'))->implode(',');
        $show->field('duration', __('Duration'));
        
        // $show->field('air_date', __('Air date'));
        // $show->field('expired_date', __('Expired date'));
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
        \Encore\Admin\Admin::script(self::JS);

        $form = new Form(new Record());

        $form->text('unique_no', __('Unique no'))->creationRules(['required', "unique:record,unique_no"]);
        $form->text('name', __('Name'))->required();
        $form->text('name2', __('English'));
        $form->multipleSelect('category', __('Category'))
            ->options(Category::getFormattedCategories('tags', true))->required();
        $form->text('duration', __('Duration'))->inputmask(['mask' => '99:99:99:99'])->required();
        $form->text('episodes', __('Episodes'));
        $form->text('ep', __('Ep'));

        // $form->date('air_date', __('Air date'));
        // $form->date('expired_date', __('Expired date'));

        $form->switch('black', __('BlackList'));

        $form->text('comment', __('Comment'));
        

        $form->saving(function(Form $form) {

            if($form->isCreating()) {
                $error = new MessageBag([
                    'title'   => '创建节目失败',
                    'message' => '该'.__('Unique no').': '. $form->unique_no.' 已存在。'
                ]);
    
                if(Record::where('unique_no', $form->unique_no)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改节目失败',
                    'message' => '该'.__('Unique no').': '. $form->unique_no.' 已存在。'
                ]);
    
                if(Record::where('unique_no', $form->air_date)->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }
            
        });

        $form->saved(function (Form $form) {
            
            CategoryRelationEvent::dispatch($form->model()->id, $form->category, 'record');
        });

        return $form;
    }

    public function unique(Request $request) {
        $data = $request->post('data');
        return response()->json(['result' => Record::where('unique_no', $data)->exists()]);
    }

    public const JS = <<<EOF
$('input[name=unique_no]').on('change', function(e) {
    var parent = $(this).parent();

    $.ajax({
        method: 'post',
        url: '/admin/media/xkc/unique',
        data: {
            data: e.currentTarget.value,
            _token:LA.token,
        },
        success: function (data) {
            if(data.result) {
                parent.addClass('has-error');
            }
            else {
                parent.removeClass('has-error');
            }
        }
    });
});
EOF;
}
