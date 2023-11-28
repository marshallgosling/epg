<?php

namespace App\Tools;

use Illuminate\Support\Facades\Log;

trait LoggerTrait {

    private $logs;
    private $log_channel;
    private $log_print = true;

    protected function info($msg)
    {
        $this->log($msg, 'info');
    }

    protected function warn($msg)
    {
        $this->log($msg, 'warning');
    }

    protected function error($msg)
    {
        $this->log($msg, 'error');
    }

    private function log($msg, $level="info")
    {
        $msg = date('Y/m/d H:i:s ') . $msg;
        if($this->log_print) {
            echo $msg.PHP_EOL;
        }
        $_ch = $this->log_channel ?? 'channel';
        Log::channel($_ch)->$level($msg);
    }
}