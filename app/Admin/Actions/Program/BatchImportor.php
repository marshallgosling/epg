<?php

namespace App\Admin\Actions\Program;

use App\Events\CategoryRelationEvent;
use App\Models\Category;
use App\Models\Program;
use Encore\Admin\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchImportor extends Action
{
    public $name = '批量导入';
    protected $selector = '.batch-importor';
    

    public function handle(Request $request)
    {
        $category = $request->get('category');
        
        $unique = $request->get('unique_no');
        $name = $request->get('name');
        $ep = (int)$request->get('ep');

        for($i=0;$i<$ep;$i++)
        {
            
        }
        
        
        return $this->response()->success(__('BatchModify success message.'))->refresh();
    }

    public function form()
    {
        $this->select('category', __('Category'))->options(Category::getXkcCategories());
        $this->text('unique_no', __('Unique no'))->placeholder('首集播出编号，后续集数自动累加');
        $this->text('name', __('Episodes'))->placeholder('剧集名称，电影无需批量导入');
        $this->text('ep', __('Ep'))->placeholder('总集数');
        
        $this->file('excel', __('Excel'))->placeholder('通过文件导入');
        //$this->text('group', __('Group'));

        $this->textarea("help", "注意说明")->default('批量添加节目内容'.PHP_EOL.'优先为根据excel文件导入，也可以通过填写必要数据生成。')->disable();
    }

    public function html()
    {
        return "<a class='batch-importor btn btn-sm btn-success'><i class='fa fa-info-circle'></i>{$this->name}</a>";
    }

}