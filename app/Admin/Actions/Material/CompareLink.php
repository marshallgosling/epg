<?php

namespace App\Admin\Actions\Material;

use App\Models\Channel;
use Encore\Admin\Actions\Action;

class CompareLink extends Action
{
    public $name = ' 节目库对比';
    protected $selector = '.compare-link';

    public $group = 'xkc';

    public function __construct($group='')
    {
        $this->group = $group;
        parent::__construct();
    }

    public function href()
    {
        return './compare/'.$this->group;
    }

    public function handle()
    {
        return $this->response();
    }

    public function html()
    {
        return '<a class="compare-link btn btn-sm btn-primary" href="'.$this->href().'"><i class="fa fa-link"></i> '.Channel::GROUPS[$this->group].__($this->name).'</a>';
    }
}