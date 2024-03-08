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
use App\Jobs\Material\MediaInfoJob;

class BatchCreator extends Action
{
    public $name = '批量创建';
    protected $selector = '.batch-creator';
    

    public function handle(Request $request)
    {
        $category = $request->get('category');
        $channel = $request->get('channel');
        $status = Material::STATUS_EMPTY;
        $duration = '00:00:00:00';
        $frames = 0;
        $list = $request->get('filelist');
        $group = "";

        foreach(explode(PHP_EOL, $list) as $item)
        {
            if(!$item) continue;
            $filepath = trim($item, '"');
            $fileinfo = explode('\\', $filepath);

            if(count($fileinfo) == 0) continue;
            $filename = array_pop($fileinfo);
            $names = explode('.', $filename);
            if(count($names)<2) continue;

            $name = $names[0];
            $unique_no = $names[1];

            if($group == "") {
                $group = preg_replace('/(\d+)$/', "", $name);
                $group = trim(trim($group), '_-');
            }
            $model = Material::where('unique_no', $unique_no)->first();
            if(!$model)
                $model = Material::create(compact('channel', 'group', 'name', 'unique_no', 'category','duration','frames','status','filepath'));
            MediaInfoJob::dispatch($model->id, 'sync')->onQueue('media');
        }
        return $this->response()->success(__('BatchCreator success message.'))->refresh();
    }

    public function handle2(Request $request)
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
        $status = Material::STATUS_EMPTY;

        for($i=0;$i<$total;$i++)
        {
            $ep = $i+$st;
            $unique_no = Str::upper($code.$this->ep($ep));
            $name = $group.' '.$ep;
            Material::create(compact('channel', 'group', 'name', 'unique_no', 'category','duration','frames','status'));
        }
        
        return $this->response()->success(__('BatchCreator success message.'))->refresh();
    }

    private function ep($idx)
    {
        return $idx>99?$idx:($idx>9?'0'.$idx:'00'.$idx);
    }

    public function form()
    {
        $this->radio('channel', __('Channel'))->options(Channel::GROUPS)->default('xkc');
        $this->select('category', __('Category'))->options(Category::getXkcCategories())->required();
        //$this->text('unique_no', __('Unique no'))->placeholder('首集播出编号，后续集数自动累加');

        //$this->text('name', __('Episodes'))->placeholder('剧集名称，电影无需批量导入')->required();
        //$this->text('st', __('起始集号'))->default(1)->placeholder('起始集号')->required();
        //$this->text('total', __('集数'))->placeholder('连续创建的集数')->required();
        //$this->text('duration', __('Duration'))->placeholder('时长')->required();
        //$this->file('excel', __('Excel'))->placeholder('通过文件导入');
        $this->textarea('filelist', __('Filepath'));
        

        $this->textarea("help", "注意说明")->default('批量添加节目内容'.PHP_EOL.'输入文件列表，系统自动进行识别生成。')->disable();
    }

    public function html()
    {
        return "<a class='batch-creator btn btn-sm btn-success'><i class='fa fa-info-circle'></i>{$this->name}</a>";
    }

}