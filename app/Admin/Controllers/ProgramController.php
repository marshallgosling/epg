<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\Program;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;
class ProgramController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '节目管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Program());

        $grid->model()->orderBy('id', 'desc');
        //$grid->column('id', __('Id'));
        $grid->column('unique_no', __('Unique no'));
        $grid->column('name', __('Name'))->expand(function() {
            
            $table = '<tr><td width="200px">'.__('Artist').'</td><td colspan="3">'.$this->artist.'</td></tr>'.
            '<tr><td width="200px">'.__('Co artist').'</td><td colspan="3">'.$this->co_artist.'</td></tr>'.
            '<tr><td width="200px">'.__('Tags').'</td><td colspan="3">'.
            '<span class="label label-warning">'.$this->mood.'</span>&nbsp;'.
            '<span class="label label-warning">'.$this->energy.'</span>&nbsp;'.
            '<span class="label label-warning">'.$this->tempo.'</span>&nbsp;'.
            '<span class="label label-danger">'.$this->gender.'</span>'.'</td></tr>'.
            '<tr><td width="200px">'.__('Genre').'</td><td>'.$this->genre.'</td><td width="200px">'.__('Author').'</td><td>'.$this->author.'</td></tr>'.
            '<tr><td width="200px">'.__('Company').'</td><td>'.$this->company.'</td><td width="200px">'.__('Lang').'</td><td>'.$this->lang.'</td></tr>'.
            '<tr><td width="200px">'.__('Product date').'</td><td>'.$this->product_date.'</td><td width="200px">'.__('Air date').'</td><td>'.$this->air_date.'</td></tr>';

            return '<table class="table table-striped">'.$table.'</table>';

        });
        $grid->column('category', __('Category'))->display(function($artist) {
            $category = array_map(function ($c) {
                return "<span class='label label-info'>{$c}</span>";
            }, $this->category);

            $tags = [];
            $tags[] = $this->mood ? "<span class='label label-warning'>{$this->mood}</span>" : '';
            $tags[] = $this->energy ? "<span class='label label-warning'>{$this->energy}</span>" : '';
            $tags[]= $this->tempo ? "<span class='label label-warning'>{$this->tempo}</span>" : '';

            $tags[] = $this->gender ? "<span class='label label-danger'>{$this->gender}</span>" : '';
            return join('&nbsp;', $category);
        });
        
        $grid->column('comment', __('Comment'));    
        /*$grid->column('gender', __('Gender'));
        $grid->column('mood', __('Mood'));
        $grid->column('energy', __('Energy'));
        $grid->column('tempo', __('Tempo'));
        $grid->column('lang', __('Lang'));
        $grid->column('duration', __('Duration'));
        $grid->column('genre', __('Genre'));
        $grid->column('author', __('Author'));
        $grid->column('lyrics', __('Lyrics'));
        */
        $grid->column('duration', __('Duration'));
        //$grid->column('co_artist', __('Co artist'));
        
        //$grid->column('product_date', __('Product date'));
        //$grid->column('air_date', __('Air date'));

        //$grid->column('created_at', __('Created at'));
        //$grid->column('updated_at', __('Updated at'));

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        
            // 在这里添加字段过滤器
            $filter->like('name', __('Name'));
            $filter->like('artist', __('Artist'));
            $filter->equal('unique_no', __('Unique_no'));
            $filter->like('category', __('Category'))->select(Category::getFormattedCategories());
        
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
        $show = new Show(Program::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('unique_no', __('Unique no'));
        $show->field('category', __('Category'));
        $show->field('album', __('Album'));
        $show->field('artist', __('Artist'));
        $show->field('co_artist', __('Co artist'));
        $show->field('gender', __('Gender'));
        $show->field('mood', __('Mood'));
        $show->field('energy', __('Energy'));
        $show->field('tempo', __('Tempo'));
        $show->field('lang', __('Lang'));
        $show->field('duration', __('Duration'));
        $show->field('genre', __('Genre'));
        $show->field('author', __('Author'));
        $show->field('lyrics', __('Lyrics'));
        $show->field('company', __('Company'));
        $show->field('air_date', __('Air date'));
        $show->field('product_date', __('Product date'));
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
        $form = new Form(new Program());

        $form->divider(__('BasicInfo'));
        $form->text('name', __('Name'))->rules('required');
        $form->text('unique_no', __('Unique no'))->rules('required');
        $form->text('duration', __('Duration'))->rules('required');
        $form->multipleSelect('category', __('Category'))->options(Category::getFormattedCategories())->rules('required');
        
        $form->text('comment', __('Comment'));

        $form->text('air_date', __('Air date'));
        $form->text('product_date', __('Product date'));
          
        $form->divider(__('TagsInfo'));
        
        $form->select('mood', __('Mood'))->options(Category::getCategories('mood'));
        $form->select('energy', __('Energy'))->options(Category::getCategories('energy'));
        $form->select('tempo', __('Tempo'))->options(Category::getCategories('tempo'));    
        $form->text('gender', __('Gender'));

        $form->text('genre', __('Genre'));
        $form->text('lang', __('Lang'));
        $form->text('author', __('Author'));
        $form->text('lyrics', __('Lyrics'));
        
        $form->text('album', __('Album'));
        $form->text('artist', __('Artist'));
        $form->text('co_artist', __('Co artist'));
        $form->text('company', __('Company'));
        
        $form->saving(function(Form $form) {

            if($form->isCreating()) {
                $error = new MessageBag([
                    'title'   => '创建节目失败',
                    'message' => '该'.__('Unique no').': '. $form->unique_no.' 已存在。'
                ]);
    
                if(Program::where('unique_no', $form->unique_no)->exists())
                {
                    return back()->with(compact('error'));
                }
            }

            if($form->isEditing()) {
                $error = new MessageBag([
                    'title'   => '修改节目失败',
                    'message' => '该'.__('Unique no').': '. $form->unique_no.' 已存在。'
                ]);
    
                if(Program::where('unique_no', $form->air_date)->where('id','<>',$form->model()->id)->exists())
                {
                    return back()->with(compact('error'));
                }
            }
            
        });

        return $form;
    }
}
