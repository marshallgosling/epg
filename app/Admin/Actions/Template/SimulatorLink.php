<?php

namespace App\Admin\Actions\Template;

use Encore\Admin\Actions\Action;

class SimulatorLink extends Action
{
    public $name = '模拟编单测试';
    protected $selector = '.simulator';

    public $group = 'xkc';

    public function __construct($group='')
    {
        $this->group = $group;
        parent::__construct();
    }

    public function href()
    {
        return admin_url('/template/simulator/'.$this->group);
    }

    public function handle()
    {
        return $this->response()->redirect(admin_url('/template/simulator/'.$this->group));
    }

    public function html()
    {
        return '<a class="simulator btn btn-sm btn-danger"><i class="fa fa-android"></i> '.__($this->name).'</a>';
    }
}