<?php
namespace App\Admin\Controllers\Template;

use App\Models\Temp\Template;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use App\Models\Temp\TemplateRecords;
use App\Tools\ChannelGenerator;
use Illuminate\Support\Facades\Storage;
use App\Admin\Actions\Template\FixStall;
use App\Admin\Extensions\MyTable;
use App\Models\Epg;
use App\Tools\Generator\XkcGenerator;
use Encore\Admin\Layout\Content;


class TempController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '【 临时 】模版';

    protected $description = [
        'index'  => "用于查看 【XKC】 自动编单问题，保存临时状态",
//        'show'   => 'Show',
//        'edit'   => 'Edit',
//        'create' => 'Create',
    ];

    /**
     * 用于模版回退
     */
    public function restore(Content $content)
    {
        $templates = Template::with('records')->where('group_id', 'xkc')->orderBy('sort', 'asc')->get();

        $data = [];
        $colors = [];
        foreach($templates as $t) {

            $temp = $t->toArray();

            $items = [];
            $programs = $t['records'];
            if($programs)foreach($programs as $p)
            {
                $style = '';
                if($p['data'] != null) {
                    $days = [];
                    if(count($p['data']['dayofweek']) == 7) $days[] = __('全天');
                    else if($p['data']['dayofweek'])
                        foreach($p['data']['dayofweek'] as $d) $days[] = __(TemplateRecords::DAYS[$d]);
                    
                    if($p['data']['result'] == '编排完' || $p['data']['result'] == '错误') $style = 'bg-danger';
                    
                    $item = [ $p['id'], $p['name'], $p['category'], TemplateRecords::TYPES[$p['type']], $p['data']['episodes'], $p['data']['date_from'].'/'.$p['data']['date_to'], implode(',', $days), $p['data']['name'], $p['data']['result'], '<a href="programs/'.$p['id'].'">查看</a>'];
                
                    $items[] = compact('style', 'item');
                }
                else {
                    $item = [ $p['id'], $p['name'], $p['category'], TemplateRecords::TYPES[$p['type']], '', '', '', '', '', '<a href="programs/'.$p->id.'">查看</a>' ];
                    $items[] = compact('style', 'item');
                }
            }

            if($t['schedule'] == Template::SPECIAL) $temp['color'] = 'default';
            else {
                if(array_key_exists($t['name'], $colors)) $temp['color'] = $colors[$t['name']];
                else {
                    $c = Epg::getNextColor();
                    $colors[$t['name']] = $c;
                    $temp['color'] = $c;
                }

            }

            $temp['table'] = (new MyTable(['ID', '别名', '栏目', '类型', '剧集', '日期范围', '播出日', '当前选集', '状态', '操作'], $items, ['table-hover']))->render();
            $data[] = $temp; 
        
        }
        $group = 'temp';
        $back = false;
        $error = Storage::disk('data')->exists(XkcGenerator::STALL_FILE) ? Storage::disk('data')->get(XkcGenerator::STALL_FILE) : "";
        return $content->title(__('Error Mode'))->description(__('Preview Template Content'))
        ->body(view('admin.template.preview', compact('data', 'group', 'error','back')));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Template());

        $grid->header(function () {
            
            if(Storage::disk('data')->exists(XkcGenerator::STALL_FILE))
                return '<small>用于查看 【XKC】 自动编单问题，保存临时状态</small> <span class="label label-warning">不可修改</span>';
            else
                return '<small>目前自动编单没有问题</small>';
        });
        $grid->model()->with('records')->where('group_id', 'xkc')->orderBy('sort', 'asc');
        //$grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->display(function($name) {
            return '<a href="temp/programs?template_id='.$this->id.'">'.$name.'</a>'; 
        });
        $grid->column('ex', __(" "))->display(function() {
            return "编排";
        })->expand(function ($model) {
            $programs = $model->records()->take(10)->get();
            $items = [];
            if($programs)foreach($programs as $p)
            {
                if($p->data != null) {
                    $days = [];
                    if(count($p->data['dayofweek']) == 7) $days[] = __('全天');
                    else if($p->data['dayofweek'])
                        foreach($p->data['dayofweek'] as $d) $days[] = __(TemplateRecords::DAYS[$d]);
                    $items[] = [ $p->sort, $p->name, $p->category, TemplateRecords::TYPES[$p->type], $p->data['episodes'], $p->data['date_from'].'/'.$p->data['date_to'], implode(',', $days), $p->data['name'], $p->data['result'], '<a href="xkc/programs/'.$p->id.'/edit">编辑</a>'];
                
                }
                else {
                    $items[] = [ $p->sort, $p->name, $p->category, TemplateRecords::TYPES[$p->type], '', '', '', '', '', '<a href="xkc/programs/'.$p->id.'/edit">编辑</a>' ];
                
                }
            }

            return new Table(['序号', '别名', '栏目', '类型', '剧集', '日期范围', '播出日', '当前选集', '状态', '操作'], $items);
        });
        $grid->column('version', __('Version'))->display(function ($version) {
            return '<span class="label label-default">'.$version.'</span>';
        });
        $grid->column('start_at', __('Start at'))->display(function($start_at) {
            $today = strtotime(date('Y-m-d 17:00:00'));
            $air = strtotime(date('Y-m-d '.$start_at));
            $html = $start_at;
            if( $air < $today ) $html .= ' <span class="label label-default">次日</span>';
            return $html;
        });
        $grid->column('end_at', __('End at'))->hide();
        $grid->column('duration', __('Duration'));
        $grid->column('schedule', __('Schedule'))->using(Template::SCHEDULES)->filter(Template::SCHEDULES);
        $grid->column('sort', __('Sort'));
        $grid->column('status', __('Status'))->filter(Template::STATUSES)->using(Template::STATUSES)->label([
            Template::STATUS_NOUSE => 'default',
            Template::STATUS_SYNCING => 'success',
            Template::STATUS_STOPED => 'danger'
        ]);
        
        $grid->column('updated_at', __('Updated at'));

        $grid->filter(function($filter){

            $filter->like('name', __('Name'));
            $filter->equal('schedule', __('Schedule'))->radio(Template::SCHEDULES);
            $filter->in('status',  __('Status'))->checkbox(Template::STATUSES);
            
        });

        $grid->disableCreateButton();
        $grid->disableBatchActions();
        $grid->disableActions();
        $grid->tools(function (Grid\Tools $tools) {
            if(Storage::disk('data')->exists(XkcGenerator::STALL_FILE))
                $tools->append(new FixStall());
        });

        // $grid->actions(function ($actions) {
        //     //$actions->add(new Programs);
        //     $actions->add(new ReplicateTemplate);
        // });

        // $grid->batchActions(function ($actions) {
        //     $actions->add(new BatchEnable);
        //     $actions->add(new BatchDisable);
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
        $show = new Show(Template::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('schedule', __('Schedule'))->using(Template::SCHEDULES);
        $show->field('start_at', __('Start at'));
        $show->field('duration', __('Duration'));
        $show->field('version', __('Version'));
        $show->field('sort', __('Sort'));
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
        $form = new Form(new Template());

        $form->text('version', __('Version'))->disable();
        $form->text('name', __('Name'))->required();
        $form->radio('schedule', __('Schedule'))->options(Template::SCHEDULES)->required();
        
        $form->text('start_at', __('Start at'))->inputmask(['mask' => '99:99:99'])->required();
        $form->text('duration', __('Duration'))->inputmask(['mask' => '99:99:99'])->required();

        $form->number('sort', __('Sort'))->min(0)->default(0);
        $form->text('comment', __('Comment'));

        $form->hidden('group_id', __('Group'))->default('xkc');
        $form->hidden('end_at', __('End at'));

        $form->saving(function(Form $form) {

            $start = strtotime('2020/01/01 '.$form->start_at);
            $start += ChannelGenerator::parseDuration($form->duration);

            $form->end_at = date('H:i:s', $start);
            
        });

        return $form;
    }
}
