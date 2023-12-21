<?php

namespace App\Admin\Controllers\Media;

use App\Events\CategoryRelationEvent;
use App\Models\Category;
use App\Models\Program;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
class ProgramController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'V China 节目库管理';

    protected $description = [
        'index'  => "V China 节目库数据",
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
        $grid = new Grid(new Program());

        $grid->model()->orderBy('id', 'desc');
        //$grid->column('id', __('Id'));
        $grid->column('unique_no', __('Unique no'))->sortable()->width(200);
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

        })->sortable();
        $grid->column('category', __('Category'))->display(function($artist) {
            $category = array_map(function ($c) {
                $t = Category::findCategory($c);
                return '<span class="label label-info" title="'.$t.'" data-toggle="tooltip" data-placement="top">'.$c.'</span>';
            }, $this->category);

            $black = $this->black ? "<span class='label label-danger'>黑名单</span>" : '';
            return $black.'&nbsp;'.join('&nbsp;', $category);
        })->sortable();
        
        $grid->column('comment', __('Comment'))->style('max-width:200px;word-break:break-all;')->hide();    
        $grid->column('artist', __('Artist'))->style('max-width:200px;word-break:break-all;');
        $grid->column('gender', __('Gender'))->hide();
        $grid->column('mood', __('Mood'))->hide();
        $grid->column('energy', __('Energy'))->hide();
        $grid->column('tempo', __('Tempo'))->hide();
        $grid->column('lang', __('Lang'))->hide();
        $grid->column('genre', __('Genre'))->hide();
        $grid->column('author', __('Author'))->hide();
        $grid->column('lyrics', __('Lyrics'))->hide();
        
        $grid->column('duration', __('Duration'))->sortable();
        $grid->column('co_artist', __('Co artist'))->hide();
        
        $grid->column('product_date', __('Product date'))->hide();
        $grid->column('air_date', __('Air date'))->hide();

        $grid->column('created_at', __('Created at'))->hide()->sortable();
        $grid->column('updated_at', __('Updated at'))->sortable();

        $grid->filter(function(Grid\Filter $filter){

            $filter->column(6, function(Grid\Filter $filter) { $filter->mlike('name', __('Name'))->placeholder('输入%作为通配符，如 灿星% 或 %灿星%');$filter->startsWith('unique_no', __('Unique_no'))->placeholder('仅支持左匹配');});
            $filter->column(6, function(Grid\Filter $filter) { $filter->mlike('artist', __('Artist'))->placeholder('输入%作为通配符，如 张学% 或 %学友%');$filter->like('category', __('Category'))->select(Category::getFormattedCategories('tags', true)); });
    
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
        \Encore\Admin\Admin::script(self::JS);

        $form = new Form(new Program());

        $form->divider(__('BasicInfo'));
        $form->text('name', __('Name'))->rules('required');
        $form->text('unique_no', __('Unique no'))->creationRules(['required', "unique:program,unique_no"]);
        $form->text('duration', __('Duration'))->inputmask(['mask' => '99:99:99:99'])->rules('required');
        $form->multipleSelect('category', __('Category'))->options(Category::getFormattedCategories())->rules('required');
        
        $form->text('comment', __('Comment'));

        $form->text('air_date', __('Air date'));
        $form->text('product_date', __('Product date'));
        $form->switch('black', __('BlackList'));
        
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

        $form->saved(function (Form $form) {
            CategoryRelationEvent::dispatch($form->model()->id, $form->category, 'program');
        });

        return $form;
    }

    public function unique(Request $request) {
        $data = $request->post('data');
        return response()->json(['result' => Program::where('unique_no', $data)->exists()]);
    }

    public const JS = <<<EOF
$('input[name=unique_no]').on('change', function(e) {
    var parent = $(this).parent();

    $.ajax({
        method: 'post',
        url: '/admin/media/programs/unique',
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
