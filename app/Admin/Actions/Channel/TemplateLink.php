<?php

namespace App\Admin\Actions\Channel;

use Encore\Admin\Actions\RowAction;

class TemplateLink extends RowAction
{
    public $name = '临时模版';

    public function href()
    {
        return "/admin/template/temp?group_id=".$this->getKey();
    }

}