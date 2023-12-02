<?php

namespace App\Admin\Models;

use Encore\Admin\Form;

class Myform extends Form
{
    public $queryString = '';

    public function resource($slice = -2): string
    {
        $segments = explode('/', trim(\request()->getUri(), '/'));

        if ($slice !== 0) {
            $segments = array_slice($segments, 0, $slice);
        }

        return implode('/', $segments).$this->queryString;
    }
}