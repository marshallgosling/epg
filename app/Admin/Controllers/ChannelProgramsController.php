<?php

namespace App\Admin\Controllers;

use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Program;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;

class ChannelProgramsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Channel【V】编单';

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
        $grid->column('name', __('Name'))->display(function($name) {
            return "<a href=\"tree/{$this->id}\">$this->name</a>"; 
        });
        $grid->column('schedule_start_at', __('Schedule start at'));
        $grid->column('schedule_end_at', __('Schedule end at'));
        $grid->column('start_at', __('Start at'));
        $grid->column('end_at', __('End at'));
        $grid->column('duration', __('Duration'));
        $grid->column('version', __('Version'));
        //$grid->column('channel_id', __('Channel id'));
        //$grid->column('data', __('Data'));
        //$grid->column('created_at', __('Created at'));
        //$grid->column('updated_at', __('Updated at'));

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->equal('channel_id', __('Air date'))->select(Channel::orderBy('id', 'desc')->limit(20)->get()->pluck('air_date', 'id'));
            
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
        //$show->field('channel_id', __('Air date'));
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
        //$form->select('channel_id', __('Air date'))->options(Channel::where);
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

        $data = is_array($model->data) ? $model->data : json_decode($model->data, true);

        $form = new \Encore\Admin\Widgets\Form();
        
        $form->action(admin_url("channel/channelv/$id/edit"));
        $form->hidden('_token')->default(csrf_token());
        $form->hidden('name')->default($model->name);
        $form->hidden('schedule_start_at')->default($model->schedule_start_at);
        $form->hidden('schedule_end_at')->default($model->schedule_end_at);
        $form->hidden('start_at')->default($model->start_at);
        $form->hidden('end_at')->default($model->end_at);
        $form->hidden('duration')->default($model->duration);
        $form->hidden('data')->default($model->data);
        
        return $content->title('节目单详细编排 '.$model->start_at.' '.$model->name)
            ->description("编排调整节目内容，节目单计划播出时间 ".$model->schedule_start_at." -> ".$model->schedule_end_at)
            ->body(view('admin.program.edit', ['model'=>$model,'data'=>$data,'form'=>$form->render()]));
    }

    public function remove($id, $idx)
    {

        $model = ChannelPrograms::findOrFail($id);

        $list = is_array($model->data) ? $model->data : json_decode($model->data, true);

        array_splice($list, (int)$idx, 1);

        $model->data = $list;

        $model->version = $model->version + 1;

        $model->save();

        $response = [
            'status'  => true,
            'message' => trans('admin.delete_succeeded'),
        ];

        return response()->json($response);
        
    }

}
