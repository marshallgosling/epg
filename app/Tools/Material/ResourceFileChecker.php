<?php
namespace App\Tools\Material;

use App\Models\Material;
use Symfony\Component\Process\Process;

class ResourceFileChecker
{
    private $group;
    private $date;
    private $epg_list;

    public function __construct($group, $date)
    {
        $this->group = $group;
        $this->date = $date;
    }
}