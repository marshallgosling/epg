<?php

namespace App\Admin\Controllers\Media;

use App\Admin\Actions\Material\AgreementLink;
use App\Admin\Actions\Material\CreateAgreement;
use App\Jobs\Material\ExpirationJob;
use App\Models\Agreement;
use App\Models\Expiration;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class ExpirationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '剧集有效期管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Expiration());
        $grid->model()->with('agreement')->orderBy('id', 'desc');

        $grid->column('id', __('Id'));
        //$grid->column('group_id', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        $grid->column('status', __('Status'))->bool();

        $grid->column('name', __('Name'));
        
        $grid->column('expiration', __('Air date').__('TimeRange'))->display(function() {
            return substr($this->agreement->start_at,0,10) .' - '. substr($this->agreement->end_at, 0, 10);
        });

        $grid->column('agreement', __('From Agreement'))->display(function() {
            return $this->agreement->name;
        });
        
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'));

        $grid->tools(function ($tools) {
            $tools->append(new CreateAgreement);
            $tools->append(new AgreementLink);
        });

        $grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
            $agreements = DB::table('agreement')->selectRaw("id,concat(name, ' (', start_at, ' ~ ', end_at,')') as name")->pluck('name', 'id')->toArray();
        
            $create->select('agreement_id', __('Agreement'))->options($agreements)->required();
            $create->select('name', __('Episodes'))->options(function ($id) {
                return [$id => $id];
            })->ajax('/admin/api/episode')->required();
            $create->select('status', __('Status'))->options(Expiration::STATUS)->default(Expiration::STATUS_READY);
        });

        $grid->filter(function (Grid\Filter $filter) {
            $filter->column(6, function(Grid\Filter $filter) { 
                $filter->like('name', __('Name'));
                
            });
            $filter->column(6, function(Grid\Filter $filter) { 
                $filter->equal('agreement_id', __('From Agreement'))->select(Agreement::pluck('name', 'id')->toArray());
                
            });
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
        $show = new Show(Expiration::findOrFail($id));

        $show->field('id', __('Id'));
        $agreements = DB::table('agreement')->selectRaw("id,concat(name, ' (', start_at, ' ~ ', end_at,')') as name")->pluck('name', 'id')->toArray();
        $show->field('agreement_id', __('Agreement'))->using($agreements);
        $show->field('name', __('Name'));
        // $show->field('start_at', __('Start at'));
        // $show->field('end_at', __('End at'));
        $show->field('status', __('Status'))->using(Expiration::STATUS);
        
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
        $form = new Form(new Expiration());

        $agreements = DB::table('agreement')->selectRaw("id,concat(name, ' (', start_at, ' ~ ', end_at,')') as name")->pluck('name', 'id')->toArray();
        $form->select('agreement_id', __('Agreement'))->options($agreements)->required();

        $form->select('name', __('Episodes'))->options(function ($id) {
                    return [$id => $id];
                })->ajax('/admin/api/episode')->required();
    
        $form->switch('status', __('Status'))->options(Expiration::STATUS)->default(Expiration::STATUS_READY);
        $form->textarea('comment', __('Comment'));
        $form->hidden('group_id', 'group');
        $form->saving(function(Form $form) {

            if($form->isCreating()) {
                $error = new MessageBag([
                    'title'   => '创建失败',
                    'message' => '该剧集名称 '. $form->name.' 已存在。',
                ]);
    
                if(Expiration::where('name', $form->name)->exists())
                {
                    return back()->with(compact('error'));
                }

            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改失败',
                    'message' => '该剧集名称 '. $form->name.' 已存在。',
                ]);
    
                if(Expiration::where('name', $form->name)->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }
            
        });

        $form->saved(function (Form $form) {

            ExpirationJob::dispatch($form->model()->id);
        
        });

        return $form;
    }
}
