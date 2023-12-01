<?php

namespace App\Admin\Controllers\Template;

use App\Admin\Models\MyGrid;
use App\Admin\Actions\Template\Advanced;
use App\Admin\Actions\Template\BatchReplicate;
use App\Admin\Actions\Template\Replicate;
use App\Models\Category;
use App\Models\Template;
use App\Models\TemplatePrograms;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

class XkvProgramsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '普通模版编排 【 XKV 】';

    private $group = 'xkv';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new MyGrid(new TemplatePrograms());

        $grid->queryString = 'template_id='.$_REQUEST['template_id'];

        $grid->column('id', __('Id'));
        
        $grid->column('sort', __('Sort'));

        $grid->column('type', __('Type'))->using(TemplatePrograms::TYPES, 0)->label(TemplatePrograms::LABELS);
        
        $grid->column('category', __('Category'));
        
        $grid->column('name', __('Alias'));
        //$grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->filter(function (Filter $filter) {
            
            $filter->equal('template_id', __('Template'))->select(Template::selectRaw("concat(start_at, ' ', name) as name, id")->where('group_id', $this->group)->get()->pluck('name', 'id'));
            
        });

        $grid->actions(function ($actions) {
            $actions->add(new Replicate);
        });

        $grid->batchActions(function ($actions) {
            $actions->add(new BatchReplicate);
        });

        $grid->tools(function (Grid\Tools $tools) {
            $advanced = new Advanced();
            $advanced->template_id = $_REQUEST['template_id'];
            $tools->append($advanced);
        });

        // $grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
            
        //     $create->select('type', __('Type'))->options(TemplatePrograms::TYPES);
        //     $create->text('category', __('Category'));
        //     $create->integer('sort', __('Sort'));
        //     $create->text('name', __('Name'));
        //     $create->text('data', __('Unique no'));
        
        //     $create->integer('template_id')->default($_REQUEST['template_id']);
        // });

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
        $show = new Show(TemplatePrograms::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('sort', __('Sort'));

        $show->field('type', __('Type'))->using(TemplatePrograms::TYPES, 0);
        $show->field('category', __('Category'));
        
        $show->field('name', __('Alias'));
        $show->field('data', __('Unique no'));

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
        $form = new Form(new TemplatePrograms());

        $form->select('template_id', __('Template'))->default($_REQUEST['template_id'])
                ->options(Template::selectRaw("concat(start_at, ' ', name) as name, id")->where('group_id', $this->group)
                ->get()->pluck('name', 'id'));
        
        $form->radio('type', __('Type'))->options(TemplatePrograms::TYPES);
        $form->select('category', __('Category'))->ajax('/admin/api/category');
        $form->select('data', __('Unique no'))->ajax('/admin/api/programs');
        
        $form->text('name', __('Alias'));

        $form->number('sort', __('Sort'))->default(0);

        $form->saved(function (Form $form) {
            $temp = Template::find($form->template_id);
            if($temp) {
                $temp->version = $temp->version + 1;
                $temp->save();
            }
        });

        return $form;
    }

    public function tree($id, Content $content)
    {
        $data = TemplatePrograms::where('template_id', $id)->orderBy('sort')->select('id','name','type','category','sort','data')->get();

        $model = Template::find($id);

        $list = Template::where('group_id', $this->group)->get();

        $template = <<<TMP
        <li class="dd-item" data-id="idx">
        <div class="dd-handle bgstyle">
            <input type="checkbox" class="grid-row-checkbox" data-id="id" autocomplete="off">&nbsp;
            <span style="display:inline-block;width:80px;"><small>类型：</small>
            <span class="label label-labelstyle">categorytype</span></span> 
            <span style="display:inline-block;width:80px;"><small>栏目：</small>
            <a href="javascript:showSearchModal(idx);" class="dd-nodrag" title="">category</a></span>
            <small> 别名：</small> name&nbsp;
            <small class="text-warning">unique_no</small>
            <span class="pull-right dd-nodrag">
                <a href="javascript:showEditorModal(idx);" title="选择"><i class="fa fa-edit"></i></a>&nbsp;
                <a href="javascript:copyProgram(idx);" title="复制"><i class="fa fa-copy"></i></a>&nbsp;
                
                <a href="javascript:deleteProgram(idx);" title="删除"><i class="fa fa-trash"></i></a>
            </span>
        </div>
    </li>
TMP;

        $form = new \Encore\Admin\Widgets\Form();
        
        $form->action(admin_url("template/xkv/$id/edit"));
        $form->radio('tttt', __('Type'))->options(TemplatePrograms::TYPES);

        $json = str_replace("'","\\'", json_encode($data->toArray()));
        
        return $content->title('高级编排模式')->description("编排调整模版内容")
            ->body(view('admin.template.'.$this->group, ['model'=>$model,'data'=>$data, 'template'=>$template,  'json'=>$json,
                    'category'=>['types'=>TemplatePrograms::TYPES,'labels'=>TemplatePrograms::LABELS], 'list'=>$list]));
    }

    public function save($id, Request $request)
    {
        $action = $request->post('action');

        if(in_array($action, ['modify','sort']))
            return $this->$action($id, $request);
    }

    private function append($id, Request $request)
    {
        $data = $request->post('data');
        $item = json_decode($data, true);
        
        $program = new TemplatePrograms();
        $program->name = $item['name'];
        $program->type = $item['type'];
        $program->category = $item['category'];
        $program->sort = $item['sort'];
        $program->template_id = $id;
        $program->data = $item['data'];
        $program->save();

        $response = [
            'status'  => true,
            'message' => trans('admin.create_succeeded'),
        ];

        return response()->json($response);

    }

    private function modify($id, Request $request)
    {
        $data = $request->post('data');
        $items = json_decode($data, true);
        $deleted = json_decode($request->post('deleted'), true);
        $list = [];

        foreach($items as $item)
        {
            if($item['id'] != '0') {
                $list[] = $item['id'];
                $program = TemplatePrograms::find($item['id']);
                if($program) {
                    $program->name = $item['name'];
                    $program->type = $item['type'];
                    $program->category = $item['category'];
                    $program->data = $item['data'];
                    if($program->isDirty()) $program->save();
                }
            }
            else {
                $program = new TemplatePrograms();
                $program->name = $item['name'];
                $program->type = $item['type'];
                $program->category = $item['category'];
                $program->data = $item['data'];
                $program->template_id = $id;
                $program->sort = $item['sort'];
                $program->save();
            }
            
        }

        foreach($deleted as $item) {
            if(in_array($item['id'], $list)) continue;

            $program = TemplatePrograms::findOrFail($item['id']);
            if($program->template_id == $id) $program->delete();
        }

        $response = [
            'status'  => true,
            'message' => trans('admin.create_succeeded'),
        ];

        return response()->json($response);
    }

    private function replace($id, Request $request)
    {
        $data = $request->post('data');
        $item = json_decode($data, true);
        
        $program = TemplatePrograms::findOrFail($item['id']);
        $program->name = $item['name'];
        $program->type = $item['type'];
        $program->category = $item['category'];
        $program->data = $item['data'];
        $program->save();

        $response = [
            'status'  => true,
            'message' => trans('admin.save_succeeded'),
        ];

        return response()->json($response);

    }

    private function sort($id, Request $request) {
        $data = $request->post('data');
        $list = json_decode($data, true);

        foreach($list as $item)
        {
            
            $template =  TemplatePrograms::find($item['id']);
            $template->sort = $item['sort'];
            if($template->isDirty())
                $template->save();
        }

        $response = [
            'status'  => true,
            'message' => trans('admin.create_succeeded'),
        ];

        return response()->json($response);
    }

    public function remove($id, $idx)
    {
        $ids = explode('_', $idx);

        foreach($ids as $idx) {
            $item = TemplatePrograms::find($idx);

            if($item && $item->template_id == $id) {
                $item->delete();
            }
    
        }
        
        $response = [
            'status'  => true,
            'message' => trans('admin.delete_succeeded'),
        ];

        return response()->json($response);
        
    }
}
