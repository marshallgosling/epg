<?php

namespace App\Admin\Controllers\Template;

use App\Admin\Actions\Template\BatchDisable;
use App\Admin\Actions\Template\BatchEnable;
use App\Models\Template;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use App\Admin\Actions\Template\ReplicateTemplate;
use App\Models\TemplateRecords;
use App\Tools\ChannelGenerator;
use Illuminate\Support\Facades\Storage;
use App\Admin\Actions\Template\FixStall;
use App\Admin\Actions\Template\SimulatorLink;
use App\Admin\Extensions\MyTable;
use App\Models\Epg;
use App\Tools\Generator\XkcGenerator;
use Encore\Admin\Layout\Content;
use Illuminate\Support\MessageBag;
use Encore\Admin\Controllers\AdminController;

class XkcController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '【 XKC 】模版';
    private $group = 'xkc';

    public function preview(Content $content)
    {
        $templates = Template::with('records')->where('group_id', 'xkc')->orderBy('sort', 'asc')->get();
        $group = $this->group;
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
                    $item = [ $p['id'], $p['name'], $p['category'], TemplateRecords::TYPES[$p['type']], $p['data']['episodes'], $p['data']['date_from'].'/'.$p['data']['date_to'], implode(',', $days), $p['data']['name'], $p['data']['result'], '<a href="programs/'.$p['id'].'/edit">编辑</a>'];
                    if($p['data']['result'] == '编排完' || $p['data']['result'] == '错误') $style = 'bg-danger';
                }
                else {
                    $item = [ $p['id'], $p['name'], $p['category'], TemplateRecords::TYPES[$p['type']], '', '', '', '', '', '<a href="programs/'.$p->id.'/edit">查看</a>' ];
                
                }
                $items[] = compact('style', 'item');
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
        
        $error = false;
        $back = true;
        return $content->title(__('Preview Mode'))->description(__('Preview Template Content'))
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

        $grid->model()->with('records')->where('group_id', 'xkc')->orderBy('sort', 'asc');
        //$grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->display(function($name) {
            return '<a href="xkc/programs?template_id='.$this->id.'">'.$name.'</a>'; 
        });
        $grid->column('ex', __(" "))->display(function() {
            return "编排";
        })->expand(function ($model) {
            $programs = $model->records()->take(10)->get();
            $items = [];
            if($programs)foreach($programs as $p)
            {
                $style = '';
                if($p->data != null) {
                    $days = [];
                    if(count($p->data['dayofweek']) == 7) $days[] = __('全天');
                    else if($p->data['dayofweek'])
                        foreach($p->data['dayofweek'] as $d) $days[] = __(TemplateRecords::DAYS[$d]);
                    $item = [ $p->id, $p->name, $p->category, TemplateRecords::TYPES[$p->type], $p->data['episodes'], $p->data['date_from'].'/'.$p->data['date_to'], implode(',', $days), $p->data['name'], $p->data['result'], '<a href="xkc/programs/'.$p->id.'/edit">编辑</a>'];
                    if($p->data['result'] == '编排完' || $p->data['result'] == '错误') $style = 'bg-danger';
                }
                else {
                    $item = [ $p->id, $p->name, $p->category, TemplateRecords::TYPES[$p->type], '', '', '', '', '', '<a href="xkc/programs/'.$p->id.'/edit">编辑</a>' ];
                }
                $items[] = compact('style', 'item');
            }

            return new MyTable(['ID', '别名', '栏目', '类型', '剧集', '日期范围', '播出日', '当前选集', '状态', '操作'], $items, ['table-hover']);
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

        $grid->actions(function ($actions) {
            //$actions->add(new Programs);
            $actions->add(new ReplicateTemplate);
        });

        $grid->batchActions(function (Grid\Tools\BatchActions $actions) {
            
            $actions->add(new BatchEnable);
            $actions->add(new BatchDisable);
        });

        $grid->tools(function (Grid\Tools $tools) {
            //$tools->disableBatchActions();
            $tools->append(new SimulatorLink);
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

    public function reset() {
        $templates = Template::with('records')->where('group_id', 'xkc')->orderBy('sort', 'asc')->get();
        $data = json_encode($templates->toArray());
        Storage::disk('data')->put('xkc_reset_template_'.date('YmdHis').'.json', $data);

        foreach($templates as $t)
        {
            $records = $t->records;

            foreach($records as $model)
            {
                $data = $model->data;
                if(key_exists('unique_no', $data)) $data['unique_no'] = '';
                if(key_exists('result', $data)) $data['result'] = '';
                if(key_exists('name', $data)) $data['name'] = '';

                if($model->type == TemplateRecords::TYPE_RANDOM) $data['episodes'] = '';

                $model->data = $data;
                if($model->isDirty()) $model->save();
            }
        }

        return response()->json(['result'=>true]);
    }
}
