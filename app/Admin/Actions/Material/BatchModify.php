<?php

namespace App\Admin\Actions\Material;

use App\Models\Category;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchModify extends BatchAction
{
    public $name = '批量修改栏目分组';
    protected $selector = '.batch-modify';

    public function handle(Collection $collection, Request $request)
    {
        $category = $request->get('category');
        $group = $request->get('group');
        foreach ($collection as $model) 
        {
            
            if($category) {
                $model->category = $category;
            }
            if($group && in_array($model->category, ['tvseries', 'tvshow','cartoon','starmade'])) {
                $model->group = $group;
            }

            if($model->isDirty())
                $model->save();
            
        }
        
        return $this->response()->success(__('BatchModify success message.'))->refresh();
    }

    public function form()
    {
        $this->select('category', __('Category'))->options(Category::getFormattedCategories());
        $this->text('group', __('Group'));

        $this->textarea("help", "注意说明")->default('批量调整物料分类和分组'.PHP_EOL.'电视剧综艺可批量调整分组')->disable();
    }

    public function html()
    {
        return "<a class='batch-modify btn btn-sm btn-warning'><i class='fa fa-info-circle'></i>{$this->name}</a>";
    }

}