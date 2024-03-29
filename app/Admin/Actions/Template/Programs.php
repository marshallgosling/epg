<?php

namespace App\Admin\Actions\Template;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class Programs extends RowAction
{
    public $name = '修改记录';

    public $group = 'xkv';

    /**
     * @return  string
     */
    public function href()
    {
        return $this->group.'/programs?template_id='.$this->getKey();
    }

}