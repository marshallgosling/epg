<?php

namespace App\Admin\Actions\Template;

use Encore\Admin\Actions\Action;

class PreviewLink extends Action
{
    public $name = '预览模版';
    protected $selector = '.preview';

    public $group = 'xkc';

    public function __construct($group='')
    {
        $this->group = $group;
        parent::__construct();
    }

    public function href()
    {
        return $this->group.'/preview';
    }

    public function handle()
    {
        return $this->response();
    }

    public function html()
    {
        return '<a class="preview btn btn-sm btn-warning" href="'.$this->href().'"><i class="fa fa-link"></i> '.__($this->name).'</a>';
    }
}