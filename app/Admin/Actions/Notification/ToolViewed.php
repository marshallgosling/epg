<?php

namespace App\Admin\Actions\Notification;

use App\Tools\Notify;
use Encore\Admin\Actions\Action;

class ToolViewed extends Action
{
    public $name = '一键已读';
    protected $selector = '.all-viewed';

    public function handle()
    {
        Notify::setViewed();
        
        return $this->response()->success(__('Viewed success message.'))->refresh();
    }

    public function html()
    {
        return '<a class="all-viewed btn btn-sm btn-danger"><i class="fa fa-eye"></i> '.__($this->name).'</a>';
    }

    public function dialog()
    {
        $this->confirm(__('Confirm all viewed?'));
    }

}