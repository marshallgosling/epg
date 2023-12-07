<?php

namespace App\Admin\Actions\Program;

use App\Models\Category;
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
            
            if($category) {
                if(in_array($category, $model->category))
                     array_splice($model->category, array_search($category, $model->category), 1);
                else
                    $model->category[] = $category;
            }
            

            if($model->isDirty())
                $model->save();
            
        }
        
        return $this->response()->success(__('BatchModify success message.'))->refresh();
    }

    public function form()
    {
        $this->select('category', __('Category'))->options(Category::getFormattedCategories());
        //$this->text('group', __('Group'));

        $this->textarea("help", "注意说明")->default('批量添加或删除栏目标签'.PHP_EOL.'默认为增加标签，如果已存在相同的标签，则会进行溢出操作')->disable();
    }

    public function html()
    {
        return "<a class='batch-modify btn btn-sm btn-warning'><i class='fa fa-info-circle'></i>{$this->name}</a>";
    }

}