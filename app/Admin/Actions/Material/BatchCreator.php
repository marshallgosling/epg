<?php

namespace App\Admin\Actions\Material;

use App\Events\CategoryRelationEvent;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Material;
use App\Models\Program;
use App\Tools\ChannelGenerator;
use Encore\Admin\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BatchCreator extends Action
{
    public $name = '批量创建';
    protected $selector = '.batch-creator';
    

    public function handle(Request $request)
    {
        $category = $request->get('category');
        $channel = $request->get('channel');
        //$unique = $request->get('unique_no');
        $group = $request->get('name');
        $total = (int)$request->get('total');
        $st = (int)$request->get('st');
        $duration = $request->get('duration');
        $frames = ChannelGenerator::parseDuration($duration) * config("FRAMES", 25);
        $code = 'XK'.Str::random(12);

        for($i=0;$i<$total;$i++)
        {
            $ep = $i+$st;
            $unique_no = Str::upper($code.$this->ep($ep));
            $name = $group.' '.$ep;
            Material::create(compact('channel', 'group', 'name', 'unique_no', 'category','duration','frames'));
        }
        
        return $this->response()->success(__('BatchCreator success message.'))->refresh();
    }

    private function ep($idx)
    {
        return $idx>99?$idx:($idx>9?'0'.$idx:'00'.$idx);
    }

    public function form()
    {
        $this->radio('channel', __('Channel'))->options(['xkc'=>'星空中国'])->default('xkc');
        $this->select('category', __('Category'))->options(Category::getXkcCategories());
        //$this->text('unique_no', __('Unique no'))->placeholder('首集播出编号，后续集数自动累加');

        $this->text('name', __('Episodes'))->placeholder('剧集名称，电影无需批量导入');
        $this->text('st', __('起始集号'))->default(1)->placeholder('起始集号');
        $this->text('total', __('总集数'))->placeholder('总集数');
        $this->text('duration', __('Duration'))->placeholder('时长');
        //$this->file('excel', __('Excel'))->placeholder('通过文件导入');
        //$this->text('group', __('Group'));

        $this->textarea("help", "注意说明")->default('批量添加节目内容'.PHP_EOL.'优先为根据excel文件导入，也可以通过填写必要数据生成。')->disable();
    }

    public function html()
    {
        return "<a class='batch-creator btn btn-sm btn-success'><i class='fa fa-info-circle'></i>{$this->name}</a>";
    }

}