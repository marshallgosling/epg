<?php

namespace App\Tools\Statistic;

interface IStatistic {
    
    public function load();
    public function scan();
    public function store($force=false);
}