<?php

namespace App\Admin\Actions\Program;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchModifyEpisodes extends BatchAction
{
    public $name = '批量修改剧集';
    protected $selector = '.batch-modify-episodes';
    public $class = '\App\Models\Record';

    public function handle(Collection $collection, Request $request)
    {
        //$category = $request->get('category');
        $episodes = $request->get('episodes');
        foreach ($collection as $model) 
        {
            
            if($episodes) {
                $model->episodes = $episodes;
            }
            

            if($model->isDirty()) {
                $model->save();
            }
                
            
        }
        
        return $this->response()->success(__('BatchModify success message.'))->refresh();
    }

    public function form()
    {
        //$this->select('category', __('Category'))->options(Category::getFormattedCategories());
        $this->text('episodes', __('Episodes'));

        $this->textarea("help", "注意说明")->default('批量添加或删除剧集信息'.PHP_EOL)->disable();
    }

    public function html()
    {
        return "<a class='batch-modify-episodes btn btn-sm btn-warning'><i class='fa fa-info-circle'></i>{$this->name}</a>";
    }

}