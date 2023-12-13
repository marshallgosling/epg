<?php
namespace App\Admin\Extensions;

use Encore\Admin\Grid\Filter\Like;

class MyLike extends Like
{
    protected $exprFormat = '{value}';
}