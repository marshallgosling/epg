<?php

namespace App\Admin\Actions\Template;

use Encore\Admin\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class Advanced extends Action
{
    public $name = '高级编辑模式';

    public $group = 'xkv';
    public $template_id;

    /**
     * @return  string
     */
    public function href()
    {
        return './tree/'.$this->template_id;
    }

    public function html()
    {
        return '<a class="advanced btn btn-sm btn-danger" href="'.$this->href().'"><i class="fa fa-dedent"></i> '.$this->name.'</a>';
    }
}