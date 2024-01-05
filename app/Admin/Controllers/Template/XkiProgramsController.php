<?php

namespace App\Admin\Controllers\Template;

use App\Admin\Actions\Template\Advanced;
use App\Admin\Actions\Template\BatchReplicate;
use App\Admin\Actions\Template\BatchReset;
use App\Admin\Actions\Template\Replicate;
use App\Admin\Extensions\MyForm;
use App\Admin\Extensions\MyGrid;
use App\Models\Category;
use App\Models\Template;
use App\Models\TemplateRecords;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Form\EmbeddedForm;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

class XkiProgramsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '普通模版编排 【 星空国际 】';

    private $group = 'xki';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new MyGrid(new TemplateRecords());

        $grid->queryString = 'template_id='.$_REQUEST['template_id'];

        $grid->column('id', __('Id'))->hide();
        
        $grid->column('sort', __('Sort'));

        $grid->column('name', __('Alias'));

        $grid->column('type', __('Type'))->filter(TemplateRecords::TYPES)->using(TemplateRecords::TYPES, 0)->label(TemplateRecords::LABELS);
        $grid->column('category', __('Category'));

        $grid->column('daysofweek', __('Daysofweek'))->display(function () {
            if($this->data != null) {
                $days = [];
                if(count($this->data['dayofweek']) == 7) return __('全天');
                if($this->data['dayofweek'])
                    foreach($this->data['dayofweek'] as $d) $days[] = TemplateRecords::DAYS[$d];
                return implode(',', $days);
            }
        });
        $grid->column('daterange', __('DateRange'))->display(function () {
            if($this->data != null) {return $this->data['date_from'].'/'.$this->data['date_to'];}
        });
        $grid->column('episodes', __('Episodes'))->display(function () {
            if($this->data != null) {return $this->data['episodes'];}
        });
        //$grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->filter(function (Filter $filter) {
            
            $filter->equal('template_id', __('Template'))->select(
                Template::selectRaw("concat(start_at, ' ', name) as name, id")
                    ->where('group_id', $this->group)
                    ->orderBy('sort', 'asc')
                    ->get()->pluck('name', 'id')
                );
            
        });

        $grid->actions(function ($actions) {
            $actions->add(new Replicate);
        });

        $grid->batchActions(function ($actions) {
            $actions->add(new BatchReplicate);
            $actions->add(new BatchReset);
        });

        $grid->tools(function (Grid\Tools $tools) {
            $advanced = new Advanced();
            $advanced->template_id = $_REQUEST['template_id'];
            //$tools->append($advanced);
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
        $show = new Show(TemplateRecords::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('sort', __('Sort'));

        $show->field('type', __('Type'))->using(TemplateRecords::TYPES, 0);
        $show->field('category', __('Category'));
        
        $show->field('name', __('Alias'));
        $show->field('data', __('Data'))->json();

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
        $form = new MyForm(new TemplateRecords());

        if(key_exists('template_id', $_REQUEST)) $form->queryString = '?template_id='.$_REQUEST['template_id'];

        $form->select('template_id', __('Template'))->default(key_exists('template_id', $_REQUEST) ? $_REQUEST['template_id']:'')
                ->options(Template::selectRaw("concat(start_at, ' ', name) as name, id")->where('group_id', $this->group)
                ->get()->pluck('name', 'id'))->required();
        $form->number('sort', __('Sort'))->min(0)->default(0);
        $form->radio('type', __('Type'))->options(TemplateRecords::TYPES)->required();
       
        $form->text('name', __('Alias'));
        //$form->text('data', __('Unique no'));
        $form->select('category', __('Category'))->options(Category::getFormattedCategories())->required();

        $form->embeds('data', __('TemplateInfo'), function (EmbeddedForm $form) {
            
            $form->select('episodes', __('Episodes'))->options('/admin/api/episodes');
            $form->dateRange('date_from', 'date_to', __('DateRange'));
            $form->checkbox('dayofweek', __('Daysofweek'))->options(TemplateRecords::DAYS)->canCheckAll();
            $form->number('ep', __('Ep no'))->min(1)->max(4)->default(1);

            $form->text("name", __('Name'));
            $form->text('unique_no', __('Unique no'));
            $form->select('result', __('Status'))->options([""=>'','编排中'=>'编排中',"编排完"=>'编排完',"错误"=>"错误"]);
            
        });
    
        $form->saved(function (Form $form) {
            $temp = Template::find($form->template_id);
            if($temp) {
                $temp->version = $temp->version + 1;
                $temp->save();
            }
        });

        // $form->tools(function (Form\Tools $tools) {
        //     $tools->disableDelete(false);
        // });
        
        return $form;
    }

    public function tree($id, Content $content)
    {
        $data = TemplateRecords::where('template_id', $id)->orderBy('sort')->select('id','name','type','category','sort','data')->get();

        $model = Template::find($id);

        $list = Template::where('group_id', $this->group)->get();

        $template = <<<TMP
        <li class="dd-item" data-id="idx">
        <div class="dd-handle bgstyle">
            <input type="checkbox" class="grid-row-checkbox" data-id="id" autocomplete="off">&nbsp;
            <span style="display:inline-block;width:80px;"><small>类型:</small>
            <span class="label label-labelstyle">categorytype</span></span> 
            <span style="display:inline-block;width:80px;"><small>栏目:</small>
            <a href="javascript:showSearchModal(idx);" class="dd-nodrag" title="">category</a></span>
            <small> 别名:</small> name&nbsp;
            <small class="text-warning">unique_no</small>
            <span class="pull-right dd-nodrag">
                <a href="javascript:showEditorModal(idx);" title="选择"><i class="fa fa-edit"></i></a>&nbsp;
                <a href="javascript:copyProgram(idx);" title="复制"><i class="fa fa-copy"></i></a>&nbsp;
                
                <a href="javascript:deleteProgram(idx);" title="删除"><i class="fa fa-trash"></i></a>
            </span>
        </div>
    </li>
TMP;

        $json = str_replace("'","\\'", json_encode($data->toArray()));
        
        return $content->title(__('Advanced Mode'))->description(__('Modify Template Content'))
            ->body(view('admin.template.'.$this->group, ['model'=>$model,'data'=>$data, 'template'=>$template,  'json'=>$json,
                    'category'=>['types'=>TemplateRecords::TYPES,'labels'=>TemplateRecords::LABELS], 'list'=>$list]));
    }

    public function save($id, Request $request)
    {
        $action = $request->post('action');

        if(in_array($action, ['modify','sort']))
            return $this->$action($id, $request);
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
                $program = TemplateRecords::find($item['id']);
                if($program) {
                    $program->name = $item['name'];
                    $program->type = $item['type'];
                    $program->category = $item['category'];
                    //$program->data = $item['data'];
                    if($program->isDirty()) $program->save();
                }
            }
            else {
                $program = new TemplateRecords();
                $program->name = $item['name'];
                $program->type = $item['type'];
                $program->category = $item['category'];
                //$program->data = $item['data'];
                $program->template_id = $id;
                $program->sort = $item['sort'];
                $program->save();
            }
            
        }

        foreach($deleted as $item) {
            if(in_array($item['id'], $list)) continue;

            $program = TemplateRecords::findOrFail($item['id']);
            if($program->template_id == $id) $program->delete();
        }

        $response = [
            'status'  => true,
            'message' => trans('admin.create_succeeded'),
        ];

        return response()->json($response);
    }

    private function sort($id, Request $request) {
        $data = $request->post('data');
        $list = json_decode($data, true);

        foreach($list as $item)
        {
            
            $template =  TemplateRecords::find($item['id']);
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
            $item = TemplateRecords::find($idx);

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
