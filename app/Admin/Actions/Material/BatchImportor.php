<?php

namespace App\Admin\Actions\Material;

use App\Models\Category;
use App\Models\Channel;
use App\Tools\ChannelGenerator;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchImportor extends BatchAction
{
    public $name = '批量导入节目库';
    protected $selector = '.batch-import';

    public function handle(Collection $models, Request $request)
    {
        $channel = $request->get('channel');
        if($channel == 'xkv') {
            $class = '\App\Models\Program';
            $relation = 'program';
        }
        else {
            $class = '\App\Models\Record';
            $relation = 'record';
        }
        
        foreach($models as $model)
        {
            
            $program = new $class();
            
            if(in_array($model->category, ['CanXin', 'drama', 'movie','Entertainm','cartoon']))
            {
                $class = '\App\Models\Record';
                $program = new $class();
                $program->episodes = $model->group;
                if(preg_match('/(\d+)$/', $model->name, $matches))
                {
                    $program->ep = (int) $matches[1];
                }
            }
            
            $program->name = $model->name;
            $program->unique_no = $model->unique_no;
            $program->duration = $model->duration;
            $program->category = [$model->category];
            $program->seconds = ChannelGenerator::parseDuration($model->duration);
            
            if($class::where('unique_no', $model->unique_no)->exists())
            {
                continue;
            }
            else {
                $program->save();
                $cid = Category::where('no', $model->category)->value('id');
                DB::table('category_'.$relation)->insert(['category_id'=>$cid, 'record_id'=>$program->id]);
            }
        }
        
        return $this->response()->success(__('Import Success message'));
    }

    public function form()
    {
        $this->radio('channel', __('Channel'))->options(Channel::GROUPS);

        $this->textarea("help", "注意说明")->default('选择需要导入的频道')->disable();
    }

    public function html()
    {
        return "<a class='batch-import btn btn-sm btn-danger'><i class='fa fa-info-circle'></i>{$this->name}</a>";
    }

}