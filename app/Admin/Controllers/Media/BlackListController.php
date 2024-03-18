<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\BlackList\Apply;
use App\Admin\Actions\BlackList\Scanner;
use App\Models\BlackList;
use App\Models\Category;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use Illuminate\Http\Request;

class BlackListController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '黑名单';

    public function results($id, Content $content)
    {
        $title = '黑名单扫描结果';
        $description = '';
        $categories = Category::getFormattedCategories();
        $template = <<<TMP
<li class="dd-item" data-id="idx">
    <div class="dd-handle bgstyle">
        <span style="display:inline-block;width:120px;">air_date</span>
        <span style="display:inline-block;width:200px;">program</span>
        <span class="textstyle" style="display:inline-block;width:160px;margin-left:10px;">start_at -- end_at</span>
        <span style="display:inline-block;width:120px;"><a class="dd-nodrag textstyle" href="javascript:showSearchModal(idx);">unique_no</a></span>
        <span class="textstyle" style="display:inline-block;width:140px;text-overflow:ellipsis"><strong>name</strong></span>
        <span class="textstyle" style="display:inline-block;width:100px;"><small>duration</small></span>
        <span class="textstyle" style="display:inline-block;width:80px;">【category】</span>
        <span class="textstyle" style="display:inline-block;width:200px;text-overflow:ellipsis">artist</span>
        <span class="pull-right dd-nodrag">
            <a href="javascript:showSearchModal(idx);" title="替换"><i class="fa fa-edit"></i> 替换</a>&nbsp;
        </span>
    </div>
</li>
TMP;
        $model = BlackList::find($id);
        $json = $this->table($model);
        $data = json_decode($model->data);
        $replace = json_encode(is_array($data->replace)?$data->replace:[]);
        return $content
            ->title($title)
            ->description($description ?? trans('admin.list'))
            ->body(view('admin.black', compact('model', 'json', 'template', 'categories', 'replace')));
    }

    public function saveReplace($id, Request $request)
    {
        $replace = $request->post('data');
        $model = BlackList::find($id);
        $data = json_decode($model->data);
        $data->replace = json_decode($replace);
        $model->data = json_encode($data);
        $model->save();
        $response = [
            'status'  => true,
            'message' => trans('admin.save_succeeded'),
        ];
        return response()->json($response);
    }

    private function table($black)
    {
        $data = [];
        if($black) {
            $list = json_decode($black->data);
            
            if($list && is_array($list->xkv))foreach($list->xkv as $xkv) {
                $programs = $xkv->programs;
                if(is_array($programs))foreach($programs as $pro)
                {
                    if(is_array($pro->items)) foreach($pro->items as $line) {
                        $idx = $pro->id.'-'.$line->offset;
                        
                        $line->id = $idx;
                        $line->air_date = date('Y-m-d H:i:s', strtotime($pro->start_at));
                        $line->program = $pro->name;
                        $data[] = $line;
                    }
                }
            }
        }
        
        return str_replace("'","\\'", json_encode($data));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BlackList());

        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('keyword', __('Keyword'))->sortable();
        $grid->column('group', __('Group'))->using(BlackList::GROUPS);
        $grid->column('status', __('Status'))->using(BlackList::STATUS)->label(['warning','danger','success','default']);
        $grid->column('scaned_at', __('Scaned at'))->sortable();
        $grid->column('list', __(' '))->display(function () {
            return $this->status == BlackList::STATUS_SCANNED ? '<a href="./blacklist/result/'.$this->id.'">处理扫描</a>':'';
        });
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->filter(function(Grid\Filter $filter){

            $filter->like('keyword', __('Keyword'));
            $filter->in('status', __('Status'))->checkbox(BlackList::STATUS);
            
        });

        $grid->actions(function ($actions) {
            $actions->add(new Scanner);
            //$actions->add(new Apply);
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
        $show = new Show(BlackList::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('keyword', __('Keyword'));
        $show->field('group', __('Group'))->using(BlackList::GROUPS);
        $show->field('status', __('Status'))->using(BlackList::STATUS);
        $show->field('scaned_at', __('Scaned at'));
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
        $form = new Form(new BlackList());

        $form->text('keyword', __('Keyword'));
        $form->radio('group', __('Group'))->options(BlackList::GROUPS);
        $form->json('data', __('Data'));
        return $form;
    }
}
