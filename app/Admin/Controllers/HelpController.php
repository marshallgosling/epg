<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;

class HelpController extends Controller
{
    public function material(Content $layout)
    {
        $title = '素材入库帮助文档';
        $ver = 'Ver 1.0';
        $content = view('admin.help.material');
        return $layout
            ->title($title)
            ->description($ver)
            ->row(view('admin.help.layout', ['content'=>$content]));
    }

    public function template(Content $layout)
    {
        $title = '模版错误处理帮助文档';
        $ver = 'Ver 1.0';
        $content = view('admin.help.template');
        return $layout
            ->title($title)
            ->description($ver)
            ->row(view('admin.help.layout', ['content'=>$content]));
    }
}