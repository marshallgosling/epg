<?php

namespace App\Admin\Controllers\Channel;

use App\Models\Category;
use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Epg;
use App\Tools\Exporter;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $grid->filter(function (Grid\Filter $filter){
            $filter->column(8, function (Grid\Filter $filter) {
                //$filter->equal('group_id', __('Group'))->radio(Channel::GROUPS);
                $filter->between('start_at', __('TimeRange'))->datetime();
            });
        });

        return $grid;
    }

    public function preview($id, Content $content)
    {
        // $channels = Channel::where('name', 'xkc')->orderBy('air_date', 'desc')->limit(300)->get();

        // if($air_date == 'latest')
        // {
        //     $model = $channels[0];
        //     $air_date = $model->air_date;
        // }
        // else {
        //     $model = Channel::where('air_date', $air_date)->first();
        // }

        $model = Channel::findOrFail($id);
        $air_date = $model->air_date;
        $group = $model->name;

        $data = Exporter::collectData($air_date, $group, function ($t) {
            return substr($t->start_at, 11).' - '. substr($t->end_at, 11). ' <small class="pull-right text-warning">'.$t->unique_no.'</small> &nbsp;'.  $t->name . ' &nbsp; <small class="text-info">'.substr($t->duration, 0, 8).'</small>';
        });

        $order = $data['order'];

        // $data = [];
        // $colors = [];
        // $spilt = 0;
        // $order = [];
        
        // $start_at = strtotime($air_date.' '.config('EPG_START_AT', '06:00:00'));
        // $pos_start = (int)Epg::where('group_id', $group)->where('start_at','>',date('Y-m-d H:i:s', $start_at-300))
        //                 ->where('start_at','<',date('Y-m-d H:i:s', $start_at+300))->orderBy('start_at', 'desc')->limit(1)->value('id');
        // $start_at += 86400;
        // $air_date = date('Y-m-d', $start_at);
        // $pos_end = (int)Epg::where('group_id', $group)->where('start_at','>',date('Y-m-d H:i:s', $start_at-300))
        //                 ->where('start_at','<',date('Y-m-d H:i:s', $start_at+300))->orderBy('start_at', 'desc')->limit(1)->value('id');

        // if($pos_start>=0 && $pos_end>$pos_start)
        // {
        //     $list = Epg::where('group_id', $group)->where('id', '>=', $pos_start)->where('id','<',$pos_end)->get();

        //     $programs = DB::table('epg')->selectRaw('distinct(program_id)')->where('id', '>=', $pos_start)->where('id','<',$pos_end)->pluck('program_id')->toArray();
        //     $programs = ChannelPrograms::select('id','name','start_at','end_at','schedule_start_at','schedule_end_at','duration')->whereIn('id', $programs)->orderBy('start_at')->get();
    
        //     foreach($programs as $key=>$pro)
        //     {
        //         $data[$pro->id] = $pro->toArray();
        //         $data[$pro->id]['items'] = [];
        //         $order[] = $pro->id;
        //         //if($pro->schedule_start_at == '06:00:00' && $key>0) $spilt = 1;
        //     }
    
        //     foreach($list as $t) {
        //         $data[$t->program_id]['items'][] = substr($t->start_at, 11).' - '. substr($t->end_at, 11). ' <small class="pull-right text-warning">'.$t->unique_no.'</small> &nbsp;'.  $t->name . ' &nbsp; <small class="text-info">'.substr($t->duration, 0, 8).'</small>';
        //     }
        // }
           
        return $content->title(__('Preview EPG Content'))->description(__(' '))
        ->body(view('admin.epg.preview', compact('data', 'model', 'order')));
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
