<?php

namespace App\Admin\Controllers\Program;

use App\Admin\Actions\ChannelProgram\BatchReplicate;
use App\Admin\Actions\ChannelProgram\Replicate;
use App\Admin\Actions\ChannelProgram\ToolCalculate;
use App\Events\Channel\CalculationEvent;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Program;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

class XkvController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Channel【XKV】编单';

    protected $description = [
        'index'  => "每日节目编单具体编排数据，可以编辑及排序",
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
        $grid = new Grid(new ChannelPrograms());

        //$grid->column('id', __('Id'));
        $grid->column('sort', __('Sort'));
        $grid->column('name', __('Name'))->display(function($name) {
            return "<a href=\"tree/{$this->id}\">$this->name</a>"; 
        });
        
        $grid->column('start_at', __('Start at'));
        $grid->column('end_at', __('End at'));
        $grid->column('duration', __('Duration'))->display(function($duration) {
            $seconds = $duration;
            $hour = floor($seconds / 3600);
            $min = floor(($seconds%3600)/60);
            $sec = ($seconds%60);
            $min = $min>9?$min:'0'.$min;
            $sec = $sec>9?$sec:'0'.$sec;
            $erro = '';
            if(abs($duration - 3600)>300) $erro = '&nbsp;<span class="label label-danger">需处理</span>';
            return  "$hour:$min:$sec ". $erro;
        });
        $grid->column('schedule_start_at', __('Schedule start at'));
        $grid->column('schedule_end_at', __('Schedule end at'))->hide();
        $grid->column('version', __('Version'));
        
        //$grid->column('data', __('Data'));
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->equal('channel_id', __('Air date'))->select(Channel::orderBy('id', 'desc')->limit(20)->get()->pluck('air_date', 'id'));
            
        });

        $grid->actions(function ($actions) {
            $actions->add(new Replicate);
        });

        $grid->batchActions(function ($actions) {
            $actions->add(new BatchReplicate);
        });

        $grid->tools(function (Grid\Tools $tools) {
            $calculate = new ToolCalculate();
            $calculate->channel_id = $_REQUEST['channel_id'];
            $tools->append($calculate);
        });

        $grid->disableCreateButton();

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
        $show = new Show(ChannelPrograms::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('schedule_start_at', __('Schedule start at'));
        $show->field('schedule_end_at', __('Schedule end at'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        $show->field('duration', __('Duration'));
        $show->field('version', __('Version'));
        $show->field('sort', __('Sort'));
        //$show->field('data', __('Data'));
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
        $form = new Form(new ChannelPrograms());

        $form->text('name', __('Name'));
        $form->text('schedule_start_at', __('Schedule start at'));
        $form->text('schedule_end_at', __('Schedule end at'));
        $form->text('start_at', __('Start at'))->disable();
        $form->text('end_at', __('End at'))->disable();
        $form->text('duration', __('Duration'))->disable();
        $form->display('version', __('Version'));
        $form->number('sort', __('Sort'));
        $form->json('data', __('Data'));

        $form->saving(function(Form $form) {
            if($form->isEditing()) {
                
                $form->version = (int)$form->version + 1;
                
            }
        });

        return $form;
    }


    public function tree($id, Content $content)
    {
        $model = ChannelPrograms::findOrFail($id);

        $data = json_decode($model->data, true);

        $data = $this->caculateDuration($data, strtotime($model->start_at));

        $list = ChannelPrograms::where("channel_id", $model->channel_id)->orderBy('id')->get();

        // $code = [];
        // foreach($data as $item) {
        //     $code[] = $item['unique_no'];
        // }

        //$artists = Program::whereIn('unique_no', $code)->select('unique_no', 'artist')->get()->pluck('artist', 'unique_no')->toArray();

        $template = <<<TMP
<li class="dd-item" data-id="idx">
    <div class="dd-handle bgstyle">
        <input type="checkbox" class="grid-row-checkbox" data-id="idx" autocomplete="off">            
        <span style="display:inline-block;width:120px;margin-left:10px;">start_at -- end_at</span>
        <span style="display:inline-block;width:120px;"><a class="dd-nodrag" href="javascript:showSearchModal(idx);">unique_no</a></span>
        <span style="display:inline-block;width:300px;text-overflow:ellipsis"><strong>name</strong></span>
        <span style="display:inline-block;width:80px;"><small>duration</small></span>
        <span style="display:inline-block;width:60px;">【category】</span>
        <span style="display:inline-block;width:300px;text-overflow:ellipsis">artist</span>
        <span class="pull-right dd-nodrag">
            <a href="javascript:deleteProgram(idx);" class="tree_branch_delete"><i class="fa fa-trash"></i></a>
        </span>
    </div>
</li>
TMP;

        $form = new \Encore\Admin\Widgets\Form();
        
        $form->action(admin_url("channel/xkv/$id/edit"));
        $form->hidden('_token')->default(csrf_token());

        $json = str_replace("'","\\'", json_encode($data));

        return $content->title($model->start_at . ' '.$model->name.' 详细编排')
            ->description("编排调整节目内容，节目单计划播出时间 ".$model->start_at." -> ".$model->end_at)
            ->body(view('admin.program.xkv', ['model'=>$model,'data'=>$data,'list'=>$list,'json'=>$json, 'template'=>$template, 'form'=>$form->render()]));
    }

    public function save($id, Request $request) {
        $data = $request->all(['data']);
        $model = ChannelPrograms::findOrFail($id);

        $model->data = $data['data'];
        /*$model->start_at = $data['start_at'];
        $model->end_at = $data['end_at'];
        $model->duration = $data['duration'];
*/
        //$model->version = $model->version + 1;

        $model->save();

        $response = [
            'status'  => true,
            'message' => trans('admin.save_succeeded'),
        ];

        CalculationEvent::dispatch($model->channel_id, $model->id);

        return response()->json($response);
    }

    public function remove($id, $idx)
    {
        $model = ChannelPrograms::findOrFail($id);
        
        $ids = explode('_', $idx);
        if(count($ids)>1) {
            sort($ids, SORT_NUMERIC);
            $ids = array_reverse($ids);    
        }
        
        $list = json_decode($model->data, true);

        foreach($ids as $idx) {
            array_splice($list, (int)$idx, 1);
        }
        
        $model->data = json_encode($list);

        //$model->version = $model->version + 1;

        $model->save();

        CalculationEvent::dispatch($model->channel_id);

        $response = [
            'status'  => true,
            'message' => trans('admin.delete_succeeded'),
        ];

        return response()->json($response);
        
    }

    private function caculateDuration($data, $start=0)
    {
        foreach($data as &$item)
        {
            $duration = explode(':', $item['duration']);
            $seconds = (int)$duration[0]*3600 + (int)$duration[1]*60 + (int)$duration[2];

            $item['start_at'] = date('H:i:s', $start);
            $item['end_at'] = date('H:i:s', $start+$seconds);

            $start += $seconds;
            
        }

        return $data;
    }



}
