<?php

namespace App\Admin\Actions\Material;

use App\Models\Category;
use App\Models\Channel;
use App\Models\Material;
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
        $episode = (int)$request->get('episode');
        if($channel == 'xkv') {
            $class = '\App\Models\Program';
            $relation = 'program';
        }
        else if($channel == 'xkc') {
            $class = '\App\Models\Record';
            $relation = 'record';
        }
        else  {
            $class = '\App\Models\Record2';
            $relation = 'record2';
        }
        $list = [];
        foreach($models as $model)
        {
            //if($model->status != Material::STATUS_READY) continue;
            
            $program = new $class();
            
            if($channel != 'xkv')
            {

                $program->episodes = $model->group;
                $program->ep = (int) $model->ep;
                if($model->comment) $program->name2 = $model->comment;

                if(preg_match('/(\d+)$/', $model->name, $matches))
                {
                    $program->ep = (int) $matches[1];
                    //$group = preg_replace('/(\d+)$/', "", $model->name);
                    //$group = trim(trim($group), '_-');
                }
            }
            
            $program->name = $model->name;
            
            $program->unique_no = $model->unique_no;
            $program->duration = $model->duration;
            $program->category = [$model->category];
            $program->seconds = ChannelGenerator::parseDuration($model->duration);
            $program->status = $model->status;
            
            if($class::where('unique_no', $model->unique_no)->exists())
            {
                continue;
            }
            else {
                $program->save();
                //$cid = Category::where('no', $model->category)->value('id');
                //DB::table('category_'.$relation)->insert(['category_id'=>$cid, 'record_id'=>$program->id]);
                $list[] = $program->toArray();
            }
        }
        \App\Tools\Operation::log($this->name, 'material/BatchImporter', 'action', $list);
        
        return $this->response()->success(__('Import Success message'));
    }

    public function form()
    {
        $this->select('channel', __('Channel'))->options(Channel::GROUPS);
        //$this->radio('episode', '整剧导入')->options(['单集', '整部剧'])->default(0);

        $this->textarea("help", "注意说明")->default('选择需要导入的频道')->disable();
    }

    public function html()
    {
        return "<a class='batch-import btn btn-sm btn-danger'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }

}