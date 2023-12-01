<?php

namespace App\Admin\Models;

use Encore\Admin\Grid;

class MyGrid extends Grid
{
    public $queryString = '';

    public function getCreateUrl()
    {   
        return sprintf(
            '%s/create%s',
            $this->resource(),
            $this->queryString ? ('?'.$this->queryString) : ''
        );
    }
}