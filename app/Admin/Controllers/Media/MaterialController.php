<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\Material\BatchCreator;
use App\Admin\Actions\Material\BatchImportor;
use App\Admin\Actions\Material\BatchModify;
use App\Admin\Actions\Material\BatchSync;
use App\Admin\Actions\Material\CheckMediaInfo;
use App\Admin\Actions\Material\Importor;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Material;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;

class MaterialController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '物料管理';

    public function result(Content $content)
    {
        $json = json_decode(Storage::get('result3.json'), true);
        $d0 = [];
        foreach($json['succ'] as $k=>$v)
        {
            $d0[] = [$k, $v];
        }
        $d1 = [];
        foreach($json['erro'] as $k=>$v)
        {
            $d1[] = [$k, $v];
        }
        $d2 = [];
        foreach($json['miss'] as $k=>$v)
        {
            $d2[] = [$k, $v];
        }
        $succ = new Table(['code','路径'], $d0);
        $erro = new Table(['id','路径'], $d1);
        $miss = new Table(['id','路径'], $d2);

        // $json = json_decode(Storage::get('result2.json'), true);
        // $d3 = [];
        // foreach($json['erro'] as $k=>$v)
        // {
        //     $d3[] = [$k, $v];
        // }
        // $d4 = [];
        // foreach($json['miss'] as $k=>$v)
        // {
        //     $d4[] = [$k, $v];
        // }

        // $erro1 = new Table(['id','路径'], $d3);
        // $miss1 = new Table(['id','路径'], $d4);

        $t =  new Tab();
        $t->add('成功', $succ);
        $t->add('未匹配', $miss);
        $t->add('错误', $erro);

        // $t->add('已播未匹配', $miss1);
        // $t->add('已播错误', $erro1);

        return $content->title('匹配结果')
        ->description("物料匹配结果")
        ->body($t->render());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Material());

        $grid->model()->orderBy('id', 'desc');
        $grid->column('channel', __('Channel'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        $grid->column('unique_no', __('Unique_no'))->width(200)->modal("查看媒体文件信息", CheckMediaInfo::class);
        $grid->column('status', __('Status'))->display(function($status) {
            return $status == Material::STATUS_READY ? '<i class="fa fa-check text-green"></i>':'<i class="fa fa-close text-red" title="'.Material::STATUS[$status].'"></i> ';
        });
        $grid->column('name', __('Name'))->display(function ($name) {
            if($this->comment) $name2 = '&nbsp; <small class="text-info" title="'.str_replace('"', '\\"', $this->comment).'" data-toggle="tooltip" data-placement="top">Eng</small>';
            else $name2 = '';
            return $name . $name2;
        });
        $grid->column('group', __('Group'));  
        $grid->column('category', __('Category'))->display(function ($category) {
            return Category::findCategory($category). '&nbsp;('.$category.')';
        });
        $grid->column('duration', __('Duration'));
        $grid->column('size', __('Size'))->hide();
        $grid->column('md5', __('MD5'))->hide();
        $grid->column('frames', __('Frames'))->sortable();
        $grid->column('created_at', __('Created at'))->sortable();
        //$grid->column('updated_at', __('Updated at'));

        //$grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->add(new Importor);
        });

        $grid->batchActions(function ($actions) {
            $actions->add(new BatchSync);
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new BatchModify);
            $tools->append(new BatchImportor);
            $tools->append(new BatchCreator);
        });

        $grid->filter(function(Grid\Filter $filter){

            $filter->mlike('name', __('Name'))->placeholder('输入%作为通配符，如 灿星% 或 %灿星%');
            $filter->startsWith('unique_no', __('Unique_no'))->placeholder('仅支持左匹配');
            $filter->equal('category', __('Category'))->select(Category::getFormattedCategories());
            $filter->equal("status", __('Status'))->select(Material::STATUS);
        
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
        $show->field('channel', __('Channel'))->using(Channel::GROUPS);
        $show->field('name', __('Name'));
        $show->field('comment', __('Name2'));
        $show->field('group', __('Group'));
        $show->field('unique_no', __('Unique no'));
        $show->field('category', __('Category'))->using(Category::getFormattedCategories());
        $show->field('duration', __('Duration'));
        $show->field('frames', __('Frames'));
        // $show->field('air_date', __('Air date'));
        // $show->field('expired_date', __('Expired date'));
        $show->field('filepath', __('Filepath'));
        $show->field('size', __('Size'));
        $show->field('md5', __('MD5'));

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
        
        \Encore\Admin\Admin::script(str_replace('FRAMES', config('FRAMES', 25), self::JS));

        $form = new Form(new Material());

        $form->divider(__('BasicInfo'));
        $form->radio('channel', __('Channel'))->options(Channel::GROUPS)->required();
        $form->text('name', __('Name'))->required();
        $form->text('comment', __('Name2'));
        $form->text('unique_no', __('Unique no'))->required();
        $form->select('category', __('Category'))->options(Category::getFormattedCategories())->required();
        $form->text('duration', __('Duration'))->inputmask(['mask' => '99:99:99:99'])->required();
        $form->text('group', __('Group'))->default('');

        // $form->date('air_date', __('Air date'));
        // $form->date('expired_date', __('Expired date'));

        $form->divider(__('FileInfo'));
        $form->text('frames', __('Frames'))->default(0);
        $form->text('filepath', __('Filepath'))->default('');
        $form->text('size', __('Size'))->default(0);
        $form->text('md5', __('MD5'))->default('');

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

    public function unique(Request $request) {
        $data = $request->post('data');
        return response()->json(['result' => Material::where('unique_no', $data)->exists()]);
    }

    public const JS = <<<EOF
$('input[name=unique_no]').on('change', function(e) {
    var parent = $(this).parent();

    $.ajax({
        method: 'post',
        url: '/admin/media/material/unique',
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
$('input[name=duration]').on('change', function(e) {
    var duration = e.currentTarget.value;
    var items = duration.split(":");
    var seconds = parseInt(items[0]) * 3600 + parseInt(items[1]) * 60 + parseInt(items[2]);

    $('input[name=frames]').val(seconds * FRAMES + parseInt(items[3]));
});
EOF;
}
