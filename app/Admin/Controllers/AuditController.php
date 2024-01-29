<?php

namespace App\Admin\Controllers;


use App\Models\Channel;
use App\Models\Material;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;

class AuditController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "节目单审核列表";

    protected $description = [
                'index'  => "查看待审核节目单 EPG",
        //        'show'   => 'Show',
        //        'edit'   => 'Edit',
        //        'create' => 'Create',
    ];

    public function preview($id, Content $content)
    {
        $model = Channel::find($id);

        $data = $model->programs()->get();
        $color = 'primary';

        $unique = [];
        foreach($data as $program)
        {
            $json = json_decode($program->data);

            if($json->replicate) continue;

            foreach($json as $item) {
                if(!in_array($item->unique_no, $unique)) $unique[] = $item->unique_no;
            }
            
        }

        $materials = Material::whereIn('unique_no', $unique)->select('unique_no', 'status')->pluck('status', 'unique_no')->toArray();
          
        return $content->title(__('Preview EPG Content'))->description(__(' '))
        ->body(view('admin.epg.audit', compact('data', 'model', 'color', 'materials')));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Channel());

        $grid->model()->where('audit_status', Channel::AUDIT_EMPTY)->orderBy('air_date', 'asc');

        $grid->column('id', __('ID'));
        $grid->column('name', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        $grid->column('uuid', __('Uuid'))->display(function($uuid) {
            return '<a href="#">'.$uuid.'</a>';
        })->hide();
        $grid->column('air_date', __('Air date'))->sortable();
 
        $grid->column('status', __('Status'))->filter(Channel::STATUS)->using(Channel::STATUS)->label(['warning','danger','success','danger']);

        $grid->column('version', __('Version'))->label('default');
        
        //$grid->column('audit_status', __('Audit status'))->filter(Channel::AUDIT)->using(Channel::AUDIT)->label(['info','success','danger']);;
        
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->batchActions(function (Grid\Tools\BatchActions $actions) {
            
            $actions->disableDelete();
        });

        $grid->tools(function (Grid\Tools $tools) {
            //$tools->disableBatchActions();
            
        });

        $grid->disableCreateButton();
        //$grid->disableBatchActions();
    
        $grid->disableActions();

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
        $show = new Show(Channel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('uuid', __('Uuid'));
        $show->field('air_date', __('Air date'));
        $show->field('name', __('Name'));
        $show->field('status', __('Status'))->using(Channel::STATUS);
        $show->field('comment', __('Comment'));
        $show->field('version', __('Version'));
        $show->field('reviewer', __('Reviewer'));
        $show->field('audit_status', __('Audit status'))->using(Channel::AUDIT);
        $show->field('audit_date', __('Audit date'));
        $show->field('distribution_date', __('Distribution date'));
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
        $form = new Form(new Channel());

        $form->hidden('name', __('Name'))->default('channelv');
        $form->text('uuid', __('Uuid'))->default((string) Str::uuid())->required();
        $form->date('air_date', __('Air date'))->required();      
        $form->radio('status', __('Status'))->options(Channel::STATUS)->required();
        $form->text('version', __('Version'))->default('1')->required();

        $form->divider(__('AuditInfo'));
        $form->text('reviewer', __('Reviewer'));
        $form->radio('audit_status', __('Audit status'))->options(Channel::AUDIT)->required();
        $form->date('audit_date', __('Audit date'));
        $form->text('comment', __('Comment'));

        $form->date('distribution_date', __('Distribution date'));

        $form->saving(function(Form $form) {

            if($form->isCreating()) {
                $error = new MessageBag([
                    'title'   => '创建节目单失败',
                    'message' => '该日期 '. $form->air_date.' 节目单已存在。',
                ]);

                //$form->name = 'channelv';
    
                if(Channel::where('air_date', $form->air_date)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改节目单失败',
                    'message' => '该日期 '. $form->air_date.' 节目单已存在。',
                ]);
    
                if(Channel::where('air_date', $form->air_date)->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            //return $form;
            
        });

        return $form;
    }
}
