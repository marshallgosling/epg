<?php

namespace App\Admin\Actions\Program;

use App\Models\Category;
use App\Models\Channel;
use App\Models\Material;
use App\Models\Program;
use App\Tools\ChannelGenerator;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class ToolCreator extends Action
{
    public $name = '批量创建';
    protected $selector = '.tool-creator';
    private $group = '';

    public function __construct($group='xkc')
    {
        $this->group = $group;
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $category = [$request->get('category')];
        $channel = $request->get('channel');
        $unique = $request->get('code');
        $episodes = $request->get('name');
        $total = (int)$request->get('total');
        $st = (int)$request->get('st');
        $duration = $request->get('name', '00:15:00:00');
        $seconds = ChannelGenerator::parseDuration($duration);
        $code = empty($unique) ? 'XK'.Str::upper(Str::random(4)):$unique;

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

        $status = $class::STATUS_EMPTY;
        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');

        for($i=0;$i<$total;$i++)
        {
            $ep = $i+$st;
            $unique_no = $code.$this->ep($ep);
            $name = $episodes.' '.$ep;
            $class::create(compact('name', 'unique_no','episodes','category', 'duration','seconds','ep','status','created_at','updated_at'));
        }
        
        return $this->response()->success(__('BatchCreator success message.'))->refresh();
    }

    private function ep($idx)
    {
        return $idx>99?$idx:($idx>9?'0'.$idx:'00'.$idx);
    }

    public function form()
    {
        //$this->select('channel', __('Channel'))->options(Channel::GROUPS)->default('xkc');
        $this->text('ttt', __('Channel'))->default(Channel::GROUPS[$this->group])->disable();
        $this->select('category', __('Category'))->options(Category::getXkcCategories())->required();
        $this->text('code', __('Unique no'))->placeholder('播出编号前缀(可选)');
        $this->text('name', __('Episodes'))->placeholder('剧集名称，电影无需批量导入')->required();
        $this->text('st', __('起始集号'))->default(1)->placeholder('起始集号')->required();
        $this->text('total', __('集数'))->placeholder('连续创建的集数')->required();
        $this->hidden('channel')->default($this->group);
        $this->text('duration', __('Duration'))->placeholder('时长: 00:15:00:00')->inputmask(['mask' => '99:99:99:99'])->required();

        $this->textarea("help", "注意说明")->default('批量添加节目记录'.PHP_EOL.'系统自动创建节目播出编号。')->disable();
    }

    public function html()
    {
        return "<a class='tool-creator btn btn-sm btn-success'><i class='fa fa-info-circle'></i> {$this->name}</a>";
    }

}