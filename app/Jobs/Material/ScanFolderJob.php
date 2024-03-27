<?php

namespace App\Jobs\Material;

use App\Models\Folder;
use App\Models\Material;
use App\Models\RawFiles;
use App\Tools\Material\RecognizeFileInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ScanFolderJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Job ID;
    private $id;

    // Action
    private $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $action)
    {
        $this->id = $id;
        $this->action = $action;
        // $this->log_channel = 'black';
        // $this->log_print = false;
    }

    public function uniqueId()
    {
        return $this->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $model = Folder::find($this->id);
        if(!$model) return;
        
        $action = $this->action;

        if(in_array($action, ['scan', 'apply']))
        {
            $this->$action($model);
        }   
        else
        {
            $model->status = Folder::STATUS_ERROR;
            $model->save();
        }
        
    }

    public function scan($folder)
    {
        $d = dir($folder->path);
        if(!$d) {
            //$folder->data = [];
            $folder->status = Folder::STATUS_ERROR;
            $folder->scaned_at = date('Y-m-d H:i:s');
            $folder->save();
            return;
        }
        $folder_id = $folder->id;
        RawFiles::where('folder_id', $folder_id)->delete();
        
        $list = [];
        while (($file = $d->read()) !== false){
            if($file != '.' && $file != '..') {
                $m = RecognizeFileInfo::recognize($file);
                if($m) {
                    $filename = $m['filename'];
                    $name = $m['name'];
                    $unique_no = $m['unique_no'];
                    $status = $name || $unique_no;
                    $created_at = date("Y-m-d H:i:s", filectime($folder->path.$filename));
                    $list[] = compact('filename', 'name', 'unique_no','status', 'folder_id','created_at');
                }
            }
        }
        $d->close();

        RawFiles::insert($list);
        //$folder->data = $list;
        $folder->status = Folder::STATUS_READY;
        $folder->scaned_at = date('Y-m-d H:i:s');
        $folder->save();
    }

    public function scanandimport($folder)
    {
        $d = dir($folder->path);
        if(!$d) {
            //$folder->data = [];
            $folder->status = Folder::STATUS_ERROR;
            $folder->scaned_at = date('Y-m-d H:i:s');
            $folder->save();
            return;
        }
        $folder_id = $folder->id;
        RawFiles::where('folder_id', $folder_id)->delete();
        
        $list = [];
        while (($file = $d->read()) !== false){
            if($file != '.' && $file != '..') {
                $m = RecognizeFileInfo::recognize($file);
                if($m) {
                    $filename = $m['filename'];
                    $name = $m['name'];
                    $unique_no = $m['unique_no'];
                    $status = $name || $unique_no;
                    $created_at = date("Y-m-d H:i:s", filectime($folder->path.$filename));

                    if($status)
                        $this->import($folder, $m);
                    else
                        $list[] = compact('filename', 'name', 'unique_no','status', 'folder_id','created_at');
                }
            }
        }
        $d->close();

        RawFiles::insert($list);
        //$folder->data = $list;
        $folder->status = Folder::STATUS_READY;
        $folder->scaned_at = date('Y-m-d H:i:s');
        $folder->save();
    }

    private function import($folder, $m)
    {
        $category = '';
        $channel = 'xkc';
        $group = '';
        $duration = '00:00:00:00';
        $frames = 0;
        $status = Material::STATUS_EMPTY;
        $unique_no = empty($m['unique_no']) ? 'XK'.Str::upper(Str::random(12)) : $m['unique_no'];
        $name = $m['name'];
        $filepath = $folder->path . $m['filename'];
        $comment = '';
        if(empty($m['unique_no'])) {
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
        $material = Material::where('unique_no', $unique_no)->first();
        if(!$material) {
            $material = new Material(compact('channel', 'group', 'name', 'unique_no', 'filepath', 'category','duration','frames','status','comment','ep'));
            $material->save();
        }
           
        MediaInfoJob::dispatch($material->id, 'sync')->onQueue('media');
        return false;
    }

    public function apply($folder)
    {
        $list = json_decode($folder->data);
        if(is_array($list))foreach($list as $idx=>$item) {

            $result = '<i class="fa fa-close text-red"></i>';
            $material = '';

            if($item->name == '' || $item->unique_no == '') {
                if($item->name) {
                    // $material = "可新建物料 (播出编号:<span class=\"label label-warning\">自动生成</span>, 节目名:<span class=\"label label-default\">{$item->name}</span>)";
                    // $result = '<i class="fa fa-check text-green"></i>';
                    $m = new Material();
                    $m->name = str_replace('.mxf','',$item->name);
                    if(preg_match('/(\d+)$/', $m->name, $match)){
                        $group = preg_replace('/(\d+)$/', "", $m->name);
                        $m->group = trim(trim($group), '_-');
                        $m->ep = $match[1];

                    }
                    $m->duration = '00:00:00:00';
                    $m->frames = 0;
                    $m->category = '';
                    $m->unique_no = 'XK'.Str::random(12);
                    $m->filepath = 'Y:\\卡通\\'.$m->name.'.'.$m->unique_no.'.mxf';
                    
                    $m->status = Material::STATUS_EMPTY;
                    $m->channel = 'xkc';
                    $m->save();
                    @copy($folder->path.'\\'.$item->name, $m->filepath);
                    @unlink($folder->path.'\\'.$item->name);
                    MediaInfoJob::dispatch($m->id, 'sync')->onQueue('media');
            
                }
                if($item->unique_no) {
                    //$material = "不可新建物料（播出编号:<span class=\"label label-default\">{$item->unique_no}</span> <span class=\"label label-danger\">缺少节目标题</span>)";
                }
            }
            else {
                $result = '<i class="fa fa-check text-green"></i>';
                $material = "可新建物料 (播出编号:<span class=\"label label-default\">{$item->unique_no}</span> 节目名:<span class=\"label label-default\">{$item->name}</span>";
            }
        }
    }

    /**
     * Get the cache driver for the unique job lock.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function uniqueVia()
    {
        return Cache::driver('redis');
    }
}
