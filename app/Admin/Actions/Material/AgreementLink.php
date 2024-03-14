<?php

namespace App\Admin\Actions\Material;

use Encore\Admin\Actions\Action;

class AgreementLink extends Action
{
    public $name = '管理合同';
    protected $selector = '.agreement-link';

    public function href()
    {
        return './agreement';
    }

    public function handle()
    {
        return $this->response();
    }

    public function html()
    {
        return '<a class="agreement-link btn btn-sm btn-primary" href="'.$this->href().'"><i class="fa fa-link"></i> '.__($this->name).'</a>';
    }
}