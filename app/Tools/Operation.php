<?php
namespace App\Tools;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Auth\Database\OperationLog;

class Operation {

    public static function log($name, $path, $method, $input)
    {
        if(config('ENABLE_OPRATION_LOG', 'false') == 'true') {
            $log = [
                'user_id' => Admin::user()->id,
                'path'    => "$name($path)",
                'method'  => $method,
                'ip'      => '',
                'input'   => json_encode($input),
            ];

            try {
                OperationLog::create($log);
            } catch (\Exception $exception) {
                // pass
            }
        }
    }
}