<?php

namespace App\Admin\Actions\Program;

use App\Events\CategoryRelationEvent;
use App\Models\Category;
use App\Models\Program;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchModify extends BatchAction
{
    public $name = '批量修改标签';
    protected $selector = '.batch-modify';
    public $class = '\App\Models\Record';

    public function handle(Collection $collection, Request $request)
    {
        $category = $request->get('category');
        //$group = $request->get('group');
        foreach ($collection as $model) 
        {
            $categories = $model->category;
            if($category) {
                if(in_array($category, $categories))
                    array_splice($categories, array_search($category, $categories), 1);
                else
                    $categories[] = $category;
                $model->category = $categories;
            }
            

            if($model->isDirty()) {
                $model->save();

                if($model instanceof Program) {
                    $table = 'program';
                }
                else
                    $table = 'record';
                
                CategoryRelationEvent::dispatch($model->id, $categories, $table);
                
            }
                
            
        }
        
        return $this->response()->success(__('BatchModify success message.'))->refresh();
    }

    public function form()
    {
        $this->select('category', __('Category'))->options(Category::getFormattedCategories());
        //$this->text('group', __('Group'));

        $this->textarea("help", "注意说明")->default('批量添加或删除栏目标签'.PHP_EOL.'默认为增加标签，如果已存在相同的标签，则会进行移除操作')->disable();
    }

    public function html()
    {
        return "<a class='batch-modify btn btn-sm btn-warning'><i class='fa fa-info-circle'></i>{$this->name}</a>";
    }

}