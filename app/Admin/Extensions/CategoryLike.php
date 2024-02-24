<?php
namespace App\Admin\Extensions;

use Encore\Admin\Grid\Filter\Like;

class CategoryLike extends Like
{
    protected $exprFormat = '%{value},%';
}