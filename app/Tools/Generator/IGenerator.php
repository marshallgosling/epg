<?php

namespace App\Tools\Generator;

use App\Models\Channel;
use App\Models\ChannelPrograms;

interface IGenerator {
    
    public function loadTemplate();
    public function generate(Channel $channel);
    public function addSpecialPrograms($programs, $sort);
    public function addProgramItem(ChannelPrograms $programs, $class);
    public function addRecordItem($templates, $maxduration, $air, $dayofweek='');
    public function addPRItem($category='');
    public function addBumperItem($schedule_end, $break_level, $class, $category='m1');
}