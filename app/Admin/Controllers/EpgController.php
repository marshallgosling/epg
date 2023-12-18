<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\Channel;
use App\Models\Epg;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;
use Encore\Admin\Layout\Content;

class EpgController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '完整节目串联单查看器';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Epg());

        $grid->model()->orderBy('start_at');
        $grid->header(function () {
            return "";
        });

        $grid->rows(function(Grid\Row $row) {

            if(in_array($row->model()['category'], Category::CATES))
                $row->setAttributes(['class'=>'bg-info']);
        });

        $grid->column('id', __('Id'));
        
        $grid->column('group_id', __('Group'))->filter(Channel::GROUPS)->using(Channel::GROUPS)->dot(Channel::DOTS, 'info');
        
        $grid->column('start_at', __('Air date'))->display(function ($start_at) {
            return substr($start_at, 0, 10);
        });;
        $grid->column('end_at', __('TimeRange'))->display(function ($end_at) {
            return substr($this->start_at, 11).' - '.substr($end_at, 11);
        });
        $grid->column('duration', __('Duration'));

        $grid->column('name', __('Name'));

        $grid->column('unique_no', __('Unique no'));
        $grid->column('category', __('Category'));
        //$grid->column('program_id', __('Program id'));
        $grid->column('comment', __('Comment'));

        $grid->disableCreateButton();
        $grid->disableBatchActions();
        $grid->disableActions();

        $grid->filter(function(Grid\Filter $filter){
            $filter->column(8, function (Grid\Filter $filter) {
                //$filter->equal('group_id', __('Group'))->radio(Channel::GROUPS);
                $filter->between('start_at', __('TimeRange'))->datetime();
            });
        });

        return $grid;
    }

    public function preview(Content $content)
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
                if($p['data'] != null) {
                    $days = [];
                    if(count($p['data']['dayofweek']) == 7) $days[] = __('全天');
                    else if($p['data']['dayofweek'])
                        foreach($p['data']['dayofweek'] as $d) $days[] = __(TemplateRecords::DAYS[$d]);
                    $items[] = [ $p['id'], $p['name'], $p['category'], TemplateRecords::TYPES[$p['type']], $p['data']['episodes'], $p['data']['date_from'].'/'.$p['data']['date_to'], implode(',', $days), $p['data']['name'], $p['data']['result'], '<a href="programs/'.$p['id'].'">查看</a>'];
                
                }
                else {
                    $items[] = [ $p['id'], $p['name'], $p['category'], TemplateRecords::TYPES[$p['type']], '', '', '', '', '', '<a href="programs/'.$p->id.'">查看</a>' ];
                
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

            $temp['table'] = (new Table(['序号', '别名', '栏目', '类型', '剧集', '日期范围', '播出日', '当前选集', '状态', '操作'], $items, ['table-hover']))->render();
            $data[] = $temp; 
        
        }
        $group = 'temp';
        $error = Storage::disk('data')->exists('generate_stall') ? Storage::disk('data')->get('generate_stall') : "";
        return $content->title(__('Error Mode'))->description(__('Preview Template Content'))
        ->body(view('admin.template.preview', compact('data', 'group', 'error')));
    }


    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Epg::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('channel_id', __('Channel id'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
        $show->field('duration', __('Duration'));
        $show->field('unique_no', __('Unique no'));
        $show->field('category', __('Category'));
        $show->field('program_id', __('Program id'));
        $show->field('comment', __('Comment'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Epg());

        $form->text('name', __('Name'));
        $form->text('channel_id', __('Channel id'));
        $form->datetime('start_at', __('Start at'))->default(date('Y-m-d H:i:s'));
        $form->datetime('end_at', __('End at'))->default(date('Y-m-d H:i:s'));
        $form->text('duration', __('Duration'));
        $form->text('unique_no', __('Unique no'));
        $form->text('category', __('Category'));
        $form->text('program_id', __('Program id'));
        $form->text('comment', __('Comment'));

        return $form;
    }
}
