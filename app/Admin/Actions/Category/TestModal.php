<?php

namespace App\Admin\Actions\Category;

use Encore\Admin\Actions\RowAction;
use Illuminate\Http\Request;
use App\Models\Category;

class TestModal extends RowAction
{
    public $name = '测试模型';

    protected $selector = '.test-modal';

    public function form()
    {
        $type = [
            1 => '广告',
            2 => '违法',
            3 => '钓鱼',
        ];

        //$this->checkbox('type', '类型')->options($type);
        $this->select('uuid', '节目ID')->options(function ($id) {
            $user = Category::find($id);
        
            if ($user) {
                return [$user->id => $user->name];
            }
        })->ajax('/admin/api/category');
        //$this->select('name', '节目ID')->options('/admin/api/category');
        $this->textarea('reason', '原因')->rules('required');
    }

    public function handle(Category $modal)
    {
        // $request ...

        return $this->response()->success('Success message...')->refresh();
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-default test-modal">测试接口</a>
HTML;
    }
}