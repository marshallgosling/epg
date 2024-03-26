<?php

namespace App\Admin\Actions\Material;

use App\Console\Commands\materialTool;
use App\Events\CategoryRelationEvent;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Material;
use App\Models\Program;
use App\Tools\ChannelGenerator;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Jobs\Material\MediaInfoJob;
use App\Models\Folder;
use App\Models\RawFiles;

class BatchCreator extends BatchAction
{
    public $name = '批量建立物料信息';
    protected $selector = '.batch-creator';

    public function handle(Collection $collection, Request $request)
    {
        $category = $request->get('category');
        $channel = $request->get('channel');
        $group = $request->get('episodes');
        $duration = '00:00:00:00';
        $frames = 0;
        $status = Material::STATUS_EMPTY;
        $folder = false;
        foreach($collection as $model)
        {
            if($model->status != RawFiles::STATUS_READY) continue;
            if(!$folder) $folder = Folder::where('id', $model->folder_id)->value('path');
            $unique_no = empty($model->unique_no) ? 'XK'.Str::upper(Str::random(12)) : $model->unique_no;
            $name = $model->name;
            $filepath = $folder . $model->filename;
            $comment = '';
            if(empty($model->unique_no)) {
                //$filepath = str_replace('.mxf', $unique_no.'.mxf', $filepath);
                //rename($folder . $model->filename, $filepath);
                $comment = 'rename';
            }
            
            $ep = 1;
            if(preg_match('/(\d+)$/', $name, $matches))
            {
                $ep = (int) $matches[1];
                if(!$group) {
                    $group = preg_replace('/(\d+)$/', "", $name);
                    $group = trim(trim($group), '_-');
                }
            }
            $m = Material::where('unique_no', $unique_no)->first();
            if(!$m) {
                $m = new Material(compact('channel', 'group', 'name', 'unique_no', 'filepath', 'category','duration','frames','status','comment','ep'));
                $m->save();
            }
            MediaInfoJob::dispatch($m->id, 'sync')->onQueue('media');
        }
        
        return $this->response()->success($this->name.'成功')->refresh();
    }

    public function form()
    {
        $this->select('channel', __('Channel'))->options(Channel::GROUPS)->default('xkc');
        $this->select('category', __('Category'))->options(Category::getXkcCategories())->required();
        $this->text('episodes', __('Episodes'))->placeholder('剧集名称(可选)');
        $this->textarea("help", "注意说明")->default('批量添加物料记录'.PHP_EOL.'根据文件列表，系统自动进行识别生成(自动生成播出编号)。')->disable();
    }

    public function html()
    {
        return "<a class='batch-creator btn btn-sm btn-success'><i class='fa fa-plus'></i> {$this->name}</a>";
    }

}