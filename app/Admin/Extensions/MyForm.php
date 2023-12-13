<?php

namespace App\Admin\Extensions;

use Encore\Admin\Form;

class MyForm extends Form
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