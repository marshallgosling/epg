<?php

namespace App\Tools\Statistic;

interface IStatistic {
    
    public function load($channel);
    public function scan();
    public function store($force=false);
}